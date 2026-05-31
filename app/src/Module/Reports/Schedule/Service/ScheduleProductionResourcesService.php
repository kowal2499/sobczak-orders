<?php

namespace App\Module\Reports\Schedule\Service;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Reports\Schedule\DTO\ScheduleEventDTO;
use App\Module\Reports\Schedule\DTO\ScheduleResourceDTO;
use Symfony\Component\Security\Core\Security;

class ScheduleProductionResourcesService
{
    private const GRANT_BY_DPT = [
        'dpt01' => 'production.show.gluing',
        'dpt02' => 'production.show.cnc',
        'dpt03' => 'production.show.grinding',
        'dpt04' => 'production.show.laquering',
        'dpt05' => 'production.show.packing',
        'dpt06' => 'production.show.intorex',
    ];

    private const GHOST_COLOR = 'rgba(108, 117, 125, 0.25)';

    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array{resources: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>}
     */
    public function getCalendarData(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $includeGhost = false
    ): array {
        $visibleDepartments = $this->getVisibleDepartments();

        if (empty($visibleDepartments)) {
            return [
                'resources' => [],
                'events' => [],
            ];
        }

        $resources = $this->buildResources($visibleDepartments);

        $search = [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'dptDateRange' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'departments' => $visibleDepartments,
            ],
        ];

        if ($includeGhost) {
            $search['hasProductionIncludingGhost'] = true;
        } else {
            $search['hasProduction'] = true;
        }

        if ($this->security->isGranted('ROLE_CUSTOMER')) {
            $user = $this->security->getUser();
            if ($user === null) {
                return ['resources' => $resources, 'events' => []];
            }
            $search['ownedBy'] = $user;
        }

        /** @var AgreementLineRM[] $lines */
        $lines = $this->agreementLineRepo->search(['search' => $search])->getResult();

        $rangeStart = (new \DateTime($start->format('Y-m-d') . ' 00:00:00'));
        $rangeEnd = (new \DateTime($end->format('Y-m-d') . ' 23:59:59'));
        $visibleDptSet = array_flip($visibleDepartments);

        $events = [];
        foreach ($lines as $line) {
            foreach ($line->getProductions() as $production) {
                if (!isset($visibleDptSet[$production->getDepartmentSlug()])) {
                    continue;
                }
                if (!$includeGhost && $production->isGhost()) {
                    continue;
                }
                $dateStart = $production->getDateStart();
                $dateEnd = $production->getDateEnd();
                if ($dateStart === null || $dateEnd === null) {
                    continue;
                }
                if ($dateStart > $rangeEnd || $dateEnd < $rangeStart) {
                    continue;
                }
                $events[] = $this->buildEvent($line, $production);
            }
        }

        return [
            'resources' => array_map(fn (ScheduleResourceDTO $r) => $r->toArray(), $resources),
            'events' => array_map(fn (ScheduleEventDTO $e) => $e->toArray(), $events),
        ];
    }

    /**
     * @return string[] sorted list of dptXX slugs the current user can see
     */
    private function getVisibleDepartments(): array
    {
        $result = [];
        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            $slug = $dept->value;
            $grant = self::GRANT_BY_DPT[$slug] ?? null;
            if ($grant === null) {
                continue;
            }
            if ($this->security->isGranted($grant)) {
                $result[] = $slug;
            }
        }
        return $result;
    }

    /**
     * @param string[] $visibleDepartments
     * @return ScheduleResourceDTO[]
     */
    private function buildResources(array $visibleDepartments): array
    {
        $visibleSet = array_flip($visibleDepartments);
        $resources = [];
        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            if (!isset($visibleSet[$dept->value])) {
                continue;
            }
            $resources[] = new ScheduleResourceDTO($dept->value, $dept->getName());
        }
        return $resources;
    }

    private function buildEvent(AgreementLineRM $line, ProductionRM $production): ScheduleEventDTO
    {
        $color = $production->isGhost() ? self::GHOST_COLOR : null;

        return new ScheduleEventDTO(
            id: 'prod-' . $production->getId(),
            resourceId: $production->getDepartmentSlug(),
            agreementLineId: $line->getAgreementLineId(),
            orderName: $line->getOrderNumber(),
            orderStatus: $this->mapStatus($production->getStatus()),
            eventType: 'order',
            dateStart: $production->getDateStart()->format('Y-m-d'),
            dateEnd: $production->getDateEnd()->format('Y-m-d'),
            meta: [
                'productionId' => $production->getId(),
                'agreementLineId' => $line->getAgreementLineId(),
                'orderNumber' => $line->getOrderNumber(),
                'customerName' => $line->getCustomerName(),
                'productName' => $line->getProductName(),
                'isStartDelayed' => (bool) $production->isStartDelayed(),
                'isGhost' => $production->isGhost(),
            ],
            color: $color,
        );
    }

    private function mapStatus(?string $status): string
    {
        return match ((string) $status) {
            (string) TaskTypes::TYPE_DEFAULT_STATUS_STARTED => 'in_progress',
            (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED => 'completed',
            (string) TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE => 'cancelled',
            default => 'pending',
        };
    }
}
