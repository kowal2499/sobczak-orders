<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\ValueObject\DepartmentGrantMap;
use App\Module\Reports\Production\DTO\AttentionLineDTO;
use App\Module\Reports\Production\DTO\AttentionProductionDTO;

/**
 * Cztery listy zamówień wymagających uwagi, liczone na dziś z zamówień w toku - niezależnie
 * od wybranego na pulpicie miesiąca. Liczba dni to dni kalendarzowe.
 */
class AttentionListsMetricStrategy extends AbstractMetricStrategy
{
    public const LIST_GRANTS = [
        'overdueOrders' => 'reports.dashboard:attention-overdue-orders',
        'unplannedOrders' => 'reports.dashboard:attention-unplanned-orders',
        'notStartedProductions' => 'reports.dashboard:attention-not-started-productions',
        'overdueProductions' => 'reports.dashboard:attention-overdue-productions',
    ];

    private const FINISHED_STATUSES = [
        TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
        TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE,
    ];

    /** @var array<string, bool> */
    private array $departmentVisibility = [];

    public function getName(): string
    {
        return 'attention_lists';
    }

    /**
     * @return array<string, AttentionLineDTO[]>
     */
    public function compute(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        bool $includeGhost = false,
        array $options = []
    ): array {
        $today = new \DateTimeImmutable('today');
        $lists = [];
        foreach (self::LIST_GRANTS as $list => $grant) {
            if ($this->security->isGranted($grant)) {
                $lists[$list] = [];
            }
        }
        if (!$lists) {
            return [];
        }

        foreach ($this->agreementLineRepo->findActiveLines($this->ownedCustomerIds()) as $line) {
            $productions = $this->visibleProductions($line);

            if (isset($lists['overdueOrders']) && $line->getConfirmedDate() < $today) {
                $lists['overdueOrders'][] = $this->toLine(
                    $line,
                    $this->daysBetween($line->getConfirmedDate(), $today),
                    array_map(fn (ProductionRM $p) => $this->toProduction($p), $productions),
                );
            }

            if (isset($lists['unplannedOrders']) && !$this->hasOrderedProduction($line)) {
                $lists['unplannedOrders'][] = $this->toLine(
                    $line,
                    $this->daysBetween($line->getAgreementCreateDate(), $today),
                );
            }

            $notStarted = [];
            $overdue = [];
            foreach ($productions as $production) {
                $status = (int) $production->getStatus();
                $dateStart = $production->getDateStart();
                $dateEnd = $production->getDateEnd();

                if ($status === TaskTypes::TYPE_DEFAULT_STATUS_AWAITS && $dateStart !== null && $dateStart < $today) {
                    $notStarted[] = $this->toProduction($production, $this->daysBetween($dateStart, $today));
                }
                if (!in_array($status, self::FINISHED_STATUSES, true) && $dateEnd !== null && $dateEnd < $today) {
                    $overdue[] = $this->toProduction($production, $this->daysBetween($dateEnd, $today));
                }
            }

            if ($notStarted && isset($lists['notStartedProductions'])) {
                $lists['notStartedProductions'][] = $this->toLine($line, $this->maxDaysLate($notStarted), $notStarted);
            }
            if ($overdue && isset($lists['overdueProductions'])) {
                $lists['overdueProductions'][] = $this->toLine($line, $this->maxDaysLate($overdue), $overdue);
            }
        }

        return array_map($this->sortedByDays(...), $lists);
    }

    /**
     * Produkcja zlecona (nie prognoza) w którymkolwiek dziale - niezależnie od grantów użytkownika,
     * bo "brak zleconej produkcji" jest cechą zamówienia, a nie tego, co widzi oglądający.
     */
    private function hasOrderedProduction(AgreementLineRM $line): bool
    {
        $defaultSlugs = TaskTypes::getDefaultSlugs();
        foreach ($line->getProductions() as $production) {
            if (!$production->isGhost() && in_array($production->getDepartmentSlug(), $defaultSlugs, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ProductionRM[]
     */
    private function visibleProductions(AgreementLineRM $line): array
    {
        return array_values(array_filter(
            $line->getProductions(),
            fn (ProductionRM $p) => !$p->isGhost() && $this->canSeeDepartment($p->getDepartmentSlug())
        ));
    }

    private function canSeeDepartment(string $slug): bool
    {
        if (!isset($this->departmentVisibility[$slug])) {
            $grant = DepartmentGrantMap::grantFor($slug);
            $this->departmentVisibility[$slug] = $grant !== null && $this->security->isGranted($grant);
        }

        return $this->departmentVisibility[$slug];
    }

    /**
     * @param AttentionProductionDTO[] $productions
     */
    private function toLine(AgreementLineRM $line, int $days, array $productions = []): AttentionLineDTO
    {
        return new AttentionLineDTO(
            $line->getAgreementLineId(),
            $line->getDisplayNumber(),
            $line->getCustomerName(),
            $line->getProductName(),
            $line->getStatus(),
            $line->getAgreementCreateDate(),
            $line->getConfirmedDate(),
            $days,
            $productions,
        );
    }

    private function toProduction(ProductionRM $production, ?int $daysLate = null): AttentionProductionDTO
    {
        return new AttentionProductionDTO(
            $production->getDepartmentSlug(),
            $production->getStatus(),
            $production->getDateStart(),
            $production->getDateEnd(),
            $daysLate,
        );
    }

    /**
     * @param AttentionProductionDTO[] $productions
     */
    private function maxDaysLate(array $productions): int
    {
        return max(array_map(fn (AttentionProductionDTO $p) => (int) $p->daysLate, $productions));
    }

    private function daysBetween(\DateTimeInterface $from, \DateTimeImmutable $today): int
    {
        $fromDay = \DateTimeImmutable::createFromInterface($from)->setTime(0, 0);

        return (int) $fromDay->diff($today)->format('%r%a');
    }

    /**
     * @param AttentionLineDTO[] $lines
     * @return AttentionLineDTO[]
     */
    private function sortedByDays(array $lines): array
    {
        usort(
            $lines,
            fn (AttentionLineDTO $a, AttentionLineDTO $b) => [$b->days, $a->agreementLineId]
                <=> [$a->days, $b->agreementLineId]
        );

        return $lines;
    }
}
