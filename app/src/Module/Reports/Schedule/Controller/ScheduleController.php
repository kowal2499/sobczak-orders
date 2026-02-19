<?php

namespace App\Module\Reports\Schedule\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScheduleController extends BaseController
{
    #[Route(path: '/capacity', methods: ['GET'])]
    public function capacitySchedule(
        Request $request,
        ScheduleCapacityService $service
    ): Response
    {
        $startStr = $request->query->get('startDate');
        $endStr = $request->query->get('endDate');

        try {
            if (!$startStr || !$endStr) {
                throw new \InvalidArgumentException('startDate and endDate are required');
            }

            $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $startStr);
            $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $endStr);

            if (!$start || $start->format('Y-m-d') !== $startStr) {
                throw new \InvalidArgumentException('Invalid startDate format. Expected Y-m-d');
            }
            if (!$end || $end->format('Y-m-d') !== $endStr) {
                throw new \InvalidArgumentException('Invalid endDate format. Expected Y-m-d');
            }

            if ($start > $end) {
                throw new \InvalidArgumentException('startDate must be before endDate');
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            array_map(
                fn(ScheduleCapacityDTO $capacityDTO) => $capacityDTO->toArray(),
                $service->calculateBurnout($start, $end)
            ),
            Response::HTTP_OK
        );
    }
}
