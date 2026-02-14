<?php

namespace App\Module\WorkConfiguration\Controller;

use App\Controller\BaseController;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\Service\WorkScheduleService;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/schedule', name: 'work_configuration_schedule_')]
class WorkScheduleController extends BaseController
{
    #[Route(path: '', name: 'create', methods: ['POST'])]
    #[IsGranted('work-configuration.schedule')]
    public function create(
        Request $request,
        WorkScheduleRepository $workScheduleRepository
    ): JsonResponse {
        $date = $request->request->get('date');
        $dayType = $request->request->get('dayType');
        $description = $request->request->get('description');

        if (!$date || !$dayType) {
            return $this->json([
                'error' => 'Date and dayType are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Invalid date format');
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid date format. Expected Y-m-d'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dayTypeEnum = ScheduleDayType::from($dayType);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid dayType. Allowed values: working, holiday, other'
            ], Response::HTTP_BAD_REQUEST);
        }

        $workSchedule = $workScheduleRepository->upsert($dateObj, $dayTypeEnum, $description);

        return $this->json($workSchedule->toArray(), Response::HTTP_CREATED);
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        WorkScheduleService $workScheduleService
    ): JsonResponse {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $type = $request->query->get('type');

        if (!$startDate || !$endDate) {
            return $this->json([
                'error' => 'startDate and endDate are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $typeEnum = null;
        if ($type !== null) {
            $typeEnum = ScheduleDayType::tryFrom($type);
            if (!$typeEnum) {
                return $this->json([
                    'error' => 'Invalid type. Allowed values: working, holiday'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $startDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
            $endDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

            if (!$startDateObj || $startDateObj->format('Y-m-d') !== $startDate) {
                throw new \InvalidArgumentException('Invalid startDate format');
            }
            if (!$endDateObj || $endDateObj->format('Y-m-d') !== $endDate) {
                throw new \InvalidArgumentException('Invalid endDate format');
            }

            $startDateObj = $startDateObj->setTime(0, 0, 0);
            $endDateObj = $endDateObj->setTime(23, 59, 59);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid date format. Expected Y-m-d'
            ], Response::HTTP_BAD_REQUEST);
        }

        $schedules = $workScheduleService->getDays($startDateObj, $endDateObj, $typeEnum);

        return $this->json(array_map(fn (WorkSchedule $schedule) => $schedule->toArray(), $schedules), Response::HTTP_OK);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('work-configuration.schedule')]
    public function delete(
        WorkSchedule $workSchedule,
        WorkScheduleRepository $workScheduleRepository
    ): JsonResponse {
        $workScheduleRepository->delete($workSchedule);

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
