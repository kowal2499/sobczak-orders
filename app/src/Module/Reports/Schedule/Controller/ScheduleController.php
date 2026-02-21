<?php

namespace App\Module\Reports\Schedule\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
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
    ): Response
    {
        $startStr = $request->query->get('startDate');
        $endStr = $request->query->get('endDate');

        $result = $this->validateDateRange($startStr, $endStr);
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
}
