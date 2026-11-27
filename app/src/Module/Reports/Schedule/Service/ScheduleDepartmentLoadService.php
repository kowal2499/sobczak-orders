<?php

namespace App\Module\Reports\Schedule\Service;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\DepartmentGrantMap;
use App\Module\Production\ValueObject\ProductionTaskStatus;
use Symfony\Component\Security\Core\Security;

class ScheduleDepartmentLoadService
{
    private const INACTIVE_STATUSES = [ProductionTaskStatus::COMPLETED, ProductionTaskStatus::NOT_APPLICABLE];

    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array{departments: list<array{id: string, name: string, hasAccess: bool}>, load: array<string, array<string, int>>}
     */
    public function getLoad(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeAgreementLineId = null,
    ): array {
        $visible = $this->getVisibleDepartments();

        $departments = [];
        foreach (DepartmentEnum::getProductionDepartments() as $department) {
            $departments[] = [
                'id' => $department->value,
                'name' => $department->getName(),
                'hasAccess' => isset($visible[$department->value]),
            ];
        }

        if (empty($visible)) {
            return ['departments' => $departments, 'load' => []];
        }

        // ownedBy is deliberately not applied: load is an aggregate and must stay company-wide
        $search = [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'hasProduction' => true,
            'dptDateRange' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'departments' => array_keys($visible),
            ],
        ];

        /** @var AgreementLineRM[] $lines */
        $lines = $this->agreementLineRepo->search(['search' => $search])->getResult();

        $rangeStart = new \DateTimeImmutable($start->format('Y-m-d'));
        $rangeEnd = new \DateTimeImmutable($end->format('Y-m-d'));

        $load = [];
        foreach ($lines as $line) {
            if ($excludeAgreementLineId !== null && $line->getAgreementLineId() === $excludeAgreementLineId) {
                continue;
            }
            foreach ($line->getProductions() as $production) {
                $slug = $production->getDepartmentSlug();
                if (!isset($visible[$slug]) || !$this->isActive($production)) {
                    continue;
                }
                $day = max($rangeStart, new \DateTimeImmutable($production->getDateStart()->format('Y-m-d')));
                $last = min($rangeEnd, new \DateTimeImmutable($production->getDateEnd()->format('Y-m-d')));
                for (; $day <= $last; $day = $day->modify('+1 day')) {
                    $key = $day->format('Y-m-d');
                    $load[$slug][$key] = ($load[$slug][$key] ?? 0) + 1;
                }
            }
        }

        foreach ($load as &$days) {
            ksort($days);
        }
        unset($days);

        return ['departments' => $departments, 'load' => $load];
    }

    private function isActive(ProductionRM $production): bool
    {
        if ($production->isGhost() || $production->getDateStart() === null || $production->getDateEnd() === null) {
            return false;
        }
        $status = ProductionTaskStatus::tryFrom((int) $production->getStatus()) ?? ProductionTaskStatus::AWAITS;

        return !in_array($status, self::INACTIVE_STATUSES, true);
    }

    /**
     * @return array<string, true>
     */
    private function getVisibleDepartments(): array
    {
        $result = [];
        foreach (DepartmentGrantMap::all() as $slug => $grant) {
            if ($this->security->isGranted($grant)) {
                $result[$slug] = true;
            }
        }

        return $result;
    }
}
