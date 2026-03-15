<?php

namespace App\Module\Reports\Schedule\Controller;

use App\Controller\BaseController;
use App\Entity\AgreementLine;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Service\WorkScheduleService;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\Utilities\DateValidationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScheduleController extends BaseController
{
    use DateValidationTrait;

    #[Route(path: '/capacity', methods: ['GET'])]
    public function capacitySchedule(
        Request $request,
        ScheduleCapacityService $service
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('startDate'),
            $request->query->get('endDate')
        );
        if ($result instanceof Response) {
            return $result;
        }

        ['start' => $start, 'end' => $end] = $result;

        return $this->json(
            array_map(
                fn(ScheduleCapacityDTO $capacityDTO) => $capacityDTO->toArray(),
                $service->calculateBurnout($start, $end)
            ),
            Response::HTTP_OK
        );
    }

    #[Route(path: '/holidays', methods: ['GET'])]
    public function holidaysSchedule(
        Request $request,
        WorkScheduleService $workScheduleService
    ): Response {

        $result = $this->validateDateRange(
            $request->query->get('startDate'),
            $request->query->get('endDate')
        );
        if ($result instanceof Response) {
            return $result;
        }

        ['start' => $start, 'end' => $end] = $result;

        $schedules = $workScheduleService->getDays($start, $end, ScheduleDayType::Holiday);

        return $this->json(
            array_map(
                fn (WorkSchedule $schedule) => $schedule->toArray(),
                $schedules
            ),
            Response::HTTP_OK
        );
    }

    #[Route(path: '/agreement-lines', methods: ['GET'])]
    public function agreementLines(
        Request $request,
        AgreementLineRMRepository $agreementLineRMRepository
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('startDate'),
            $request->query->get('endDate')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $startDate, 'end' => $endDate] = $result;

        $criteria = [
            'search' => [
                'hasProduction' => true,
                'statusNot' => [AgreementLine::STATUS_DELETED],
                'dateStart' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ]
        ];

        $results = $agreementLineRMRepository->search($criteria)->getResult();

        return $this->json(array_map(fn($item) => $item->toArray(), $results));
    }
}
