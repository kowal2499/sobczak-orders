<?php

namespace App\Module\Reports\Schedule\Service;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\ProductionTaskStatus;
use Symfony\Component\Security\Core\Security;

/**
 * Buduje dane dla "Kalendarza zamówień": jeden wiersz na zamówienie (AgreementLine),
 * zakres = agreementCreateDate -> confirmedDate, oraz zadania produkcyjne per dział
 * (ujawniane po stronie frontu po kliknięciu "pokaż szczegóły").
 *
 * W odróżnieniu od kalendarza produkcji zamówienia NIE są bramkowane przez ROLE_PRODUCTION —
 * gdy użytkownik nie widzi żadnego działu, zwracamy zamówienia bez listy zadań produkcyjnych.
 */
class ScheduleOrderResourcesService
{
    private const GRANT_BY_DPT = [
        'dpt01' => 'production.show.gluing',
        'dpt02' => 'production.show.cnc',
        'dpt03' => 'production.show.grinding',
        'dpt04' => 'production.show.laquering',
        'dpt05' => 'production.show.packing',
        'dpt06' => 'production.show.intorex',
    ];

    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array{orders: array<int, array<string, mixed>>}
     */
    public function getCalendarData(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $visibleDptSet = array_flip($this->getVisibleDepartments());

        $search = [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'orderDateRange' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'sort' => 'dateConfirmed_asc',
        ];

        if ($this->security->isGranted('ROLE_CUSTOMER')) {
            $user = $this->security->getUser();
            if ($user === null) {
                return ['orders' => []];
            }
            $search['ownedBy'] = $user;
        }

        /** @var AgreementLineRM[] $lines */
        $lines = $this->agreementLineRepo->search(['search' => $search])->getResult();

        $orders = [];
        foreach ($lines as $line) {
            $orders[] = [
                'id' => $line->getAgreementLineId(),
                'orderNumber' => $line->getOrderNumber(),
                'customerName' => $line->getCustomerName(),
                'productName' => $line->getProductName(),
                'status' => $line->getStatus(),
                'dateStart' => $line->getAgreementCreateDate()->format('Y-m-d'),
                'dateEnd' => $line->getConfirmedDate()->format('Y-m-d'),
                'productions' => $this->buildProductions($line, $visibleDptSet),
            ];
        }

        return ['orders' => $orders];
    }

    /**
     * @param array<string, int> $visibleDptSet
     * @return array<int, array<string, mixed>>
     */
    private function buildProductions(AgreementLineRM $line, array $visibleDptSet): array
    {
        $productions = [];
        foreach ($line->getProductions() as $production) {
            if (!isset($visibleDptSet[$production->getDepartmentSlug()])) {
                continue;
            }
            if ($production->isGhost()) {
                continue;
            }
            $dateStart = $production->getDateStart();
            $dateEnd = $production->getDateEnd();
            if ($dateStart === null || $dateEnd === null) {
                continue;
            }
            $productions[] = $this->buildProduction($production);
        }

        return $productions;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProduction(ProductionRM $production): array
    {
        $slug = $production->getDepartmentSlug();
        $department = DepartmentEnum::tryFrom($slug);

        return [
            'id' => $production->getId(),
            'departmentSlug' => $slug,
            'departmentName' => $department?->getName() ?? $slug,
            'dateStart' => $production->getDateStart()->format('Y-m-d'),
            'dateEnd' => $production->getDateEnd()->format('Y-m-d'),
            'status' => $this->mapStatus($production->getStatus()),
        ];
    }

    /**
     * @return string[] sorted list of dptXX slugs the current user can see
     */
    private function getVisibleDepartments(): array
    {
        $result = [];
        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            $grant = self::GRANT_BY_DPT[$dept->value] ?? null;
            if ($grant === null) {
                continue;
            }
            if ($this->security->isGranted($grant)) {
                $result[] = $dept->value;
            }
        }

        return $result;
    }

    private function mapStatus(?string $status): string
    {
        $taskStatus = ProductionTaskStatus::tryFrom((int) $status) ?? ProductionTaskStatus::AWAITS;

        return match ($taskStatus) {
            ProductionTaskStatus::AWAITS => 'awaits',
            ProductionTaskStatus::STARTED => 'started',
            ProductionTaskStatus::IN_PROGRESS => 'in_progress',
            ProductionTaskStatus::COMPLETED => 'completed',
            ProductionTaskStatus::NOT_APPLICABLE => 'not_applicable',
        };
    }
}
