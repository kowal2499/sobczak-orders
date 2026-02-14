<?php

namespace App\Module\WorkConfiguration\Controller;

use App\Controller\BaseController;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/capacity', name: 'work_configuration_capacity_')]
class WorkCapacityController extends BaseController
{
    #[Route(path: '', name: 'create', methods: ['POST'])]
    #[IsGranted('work-configuration.capacity')]
    public function create(
        Request $request,
        WorkCapacityRepository $workCapacityRepository
    ): JsonResponse
    {
        $dateFrom = $request->request->get('dateFrom');
        $capacity = $request->request->get('capacity');

        if (!$dateFrom || !$capacity) {
            return $this->json([
                'error' => 'dateFrom and capacity are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateFromObj = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
            if (!$dateFromObj || $dateFromObj->format('Y-m-d') !== $dateFrom) {
                throw new \InvalidArgumentException('Invalid date format');
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid date format. Expected Y-m-d'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($capacity) || (float)$capacity <= 0) {
            return $this->json([
                'error' => 'Invalid capacity. Must be greater than 0'
            ], Response::HTTP_BAD_REQUEST);
        }

        $workCapacity = $workCapacityRepository->upsert($dateFromObj, (float)$capacity);

        return $this->json($workCapacity->toArray(), Response::HTTP_CREATED);
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        WorkCapacityRepository $workCapacityRepository
    ): JsonResponse
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        try {
            $startDateObj = $startDate ? DateTimeImmutable::createFromFormat('Y-m-d', $startDate) : null;
            $endDateObj = $endDate ? DateTimeImmutable::createFromFormat('Y-m-d', $endDate) : null;
            if ($startDateObj === false || $endDateObj === false) {
                throw new \InvalidArgumentException('Invalid date format');
            }

            if ($startDateObj) {
                if ($startDateObj->format('Y-m-d') !== $startDate) {
                    throw new \InvalidArgumentException('Invalid startDate format');
                }
                $startDateObj = $startDateObj->setTime(0, 0, 0);
            }

            if ($endDateObj) {
                if ($endDateObj->format('Y-m-d') !== $endDate) {
                    throw new \InvalidArgumentException('Invalid endDate format');
                }
                $endDateObj = $endDateObj->setTime(23, 59, 59);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid date format. Expected Y-m-d'
            ], Response::HTTP_BAD_REQUEST);
        }

        $capacities = $workCapacityRepository->findByRange($startDateObj, $endDateObj);

        return $this->json(array_map(fn (WorkCapacity $capacity) => $capacity->toArray(), $capacities), Response::HTTP_OK);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('work-configuration.capacity')]
    public function delete(
        WorkCapacity           $workCapacity,
        WorkCapacityRepository $workCapacityRepository
    ): JsonResponse
    {
        $workCapacityRepository->delete($workCapacity);

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}