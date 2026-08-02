<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\WorkConfiguration\Service\WorkingDayCalculator;
use Symfony\Component\Security\Core\Security;

/**
 * Miernik "Departments Bonus - w terminie". Jak DepartmentsBonusMetricStrategy (produkcje działów
 * domyślnych ukończone w zakresie miesiąca, współczynnik factorBonus), ale dodatkowo oznacza
 * terminowość: onTime=true tylko gdy completedAt mieści się w zaplanowanym oknie [dateStart, dateEnd].
 *
 * Rekordy poza oknem NIE są odfiltrowywane (kwalifikacja bez zmian) - trafiają do wyniku z onTime=false,
 * aby front mógł je pokazać wyszarzone (0 pkt). Brak zaplanowanego okna (null start/end) => onTime=false.
 * Agregat firmowy (bez filtra ROLE_CUSTOMER).
 */
class DepartmentsBonusOnTimeMetricStrategy extends AbstractProductionRecordStrategy
{
    /**
     * Domyślne widełki terminowości w dniach roboczych. Wartość obowiązująca dla rozliczeń -
     * suwak na pulpicie nadpisuje ją wyłącznie na czas podglądu (patrz $options['toleranceDays']).
     */
    public const DEFAULT_TOLERANCE_DAYS = 5;

    /** Górna granica sanityzacji parametru z requestu. */
    private const MAX_TOLERANCE_DAYS = 30;

    private int $toleranceDays = self::DEFAULT_TOLERANCE_DAYS;

    public function __construct(
        AgreementLineRMRepository $agreementLineRepo,
        Security $security,
        private readonly WorkingDayCalculator $workingDayCalculator,
    ) {
        parent::__construct($agreementLineRepo, $security);
    }

    public function getName(): string
    {
        return 'departments_bonus_on_time';
    }

    public function compute(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        bool $includeGhost = false,
        array $options = []
    ): array {
        $this->toleranceDays = $this->sanitizeTolerance($options['toleranceDays'] ?? null);

        return parent::compute($start, $end, $includeGhost, $options);
    }

    private function sanitizeTolerance(mixed $value): int
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return self::DEFAULT_TOLERANCE_DAYS;
        }

        return max(0, min(self::MAX_TOLERANCE_DAYS, (int) $value));
    }

    protected function buildSearch(\DateTimeInterface $start, \DateTimeInterface $end, bool $includeGhost): array
    {
        return [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'hasProduction' => true,
        ];
    }

    protected function qualifies(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        if (true !== $production->isCompleted()) {
            return false;
        }
        $completedAt = $production->getCompletedAt();

        return $completedAt !== null && $completedAt >= $rangeStart && $completedAt <= $rangeEnd;
    }

    protected function emitsOutOfRange(): bool
    {
        return true;
    }

    protected function isOnTime(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        return $this->fitsWindow($production, $this->toleranceDays);
    }

    protected function appliedTolerance(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        // premia należy się, ale samo okno bez widełek by jej nie dało
        return 0 !== $this->toleranceDays && !$this->fitsWindow($production, 0);
    }

    protected function timelinessWorkingDays(ProductionRM $production): int
    {
        $dateStart = $production->getDateStart();
        $dateEnd = $production->getDateEnd();
        $completedAt = $production->getCompletedAt();

        if ($dateStart === null || $dateEnd === null || $completedAt === null) {
            return 0;
        }

        if ($completedAt > $dateEnd) {
            return $this->workingDayCalculator->countWorkingDays($dateEnd, $completedAt);
        }
        if ($completedAt < $dateStart) {
            return -$this->workingDayCalculator->countWorkingDays($completedAt, $dateStart);
        }

        return 0;
    }

    /**
     * Czy ukończenie mieści się w zaplanowanym oknie rozszerzonym o podaną liczbę dni roboczych
     * (weekendy i święta widełek nie konsumują). $toleranceDays = 0 to reguła ścisła.
     */
    private function fitsWindow(ProductionRM $production, int $toleranceDays): bool
    {
        $dateStart = $production->getDateStart();
        $dateEnd = $production->getDateEnd();
        $completedAt = $production->getCompletedAt();

        if ($dateStart === null || $dateEnd === null || $completedAt === null) {
            return false;
        }

        $windowStart = new \DateTime(
            $this->workingDayCalculator->shift($dateStart, -$toleranceDays)->format('Y-m-d') . ' 00:00:00'
        );
        $windowEnd = new \DateTime(
            $this->workingDayCalculator->shift($dateEnd, $toleranceDays)->format('Y-m-d') . ' 23:59:59'
        );

        return $completedAt >= $windowStart && $completedAt <= $windowEnd;
    }

    protected function factorsOf(ProductionRM $production): ?AssembledFactorDTO
    {
        return $production->getFactorBonusCompletedTasks() ?? $production->getFactorBonus();
    }
}
