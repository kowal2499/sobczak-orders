<?php


namespace App\Service;


use App\Repository\AgreementLineRepository;
use Doctrine\ORM\EntityManagerInterface;

class CockpitIndicatorsService
{
    private const FACTORS_PER_DAY = 1.5238;
    private $entityManager;
    private $agreementLineRepository;

    private $start;
    private $end;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AgreementLineRepository $agreementLineRepository
     */
    public function __construct(EntityManagerInterface $entityManager, AgreementLineRepository $agreementLineRepository)
    {
        $this->entityManager = $entityManager;
        $this->agreementLineRepository = $agreementLineRepository;
    }

    private function getTotalFactors()
    {
        if (!$this->start || !$this->end) {
            return 0;
        }
    }

    public function calculate(\DateTimeImmutable $date)
    {
        $workingSchedule = new WorkingScheduleService($this->entityManager);
        $workingSchedule->initialize($date->format('Y-m-d'));

        $this->start = $date->modify('first day of this month')->setTime(0, 0, 0);
        $this->end = $date->modify('last day of this month')->setTime(23, 59, 59, 999999);

        $workingDaysCount = $workingSchedule->getWorkingDaysCount();

        $factorsLimit = floor(self::FACTORS_PER_DAY * $workingDaysCount);
        $totalFactorsInOrders = $this->agreementLineRepository->getAllOrdersFactorsSummaryByFactorMethod($this->start,  $this->end) ?? 0;

        $finishedFactors = $this->agreementLineRepository->getCompletedOrdersFactorsSummaryByFactorMethod($this->start, $this->end) ?? 0;
        $totalQuantity = $this->agreementLineRepository->getAllOrdersByFactorMethod($this->start,  $this->end);
        $finishedQuantity = $this->agreementLineRepository->getCompletedOrdersByFactorMethod($this->start,  $this->end);

        return [
            'workingSchedule' => [
                'workingDaysCount' => $workingDaysCount,
                'factorsLimit' => $factorsLimit
            ],
            'allOrders' => [
                'total' => $totalQuantity,
                'totalFactors' => sprintf("%.2f", $totalFactorsInOrders),
                'productionCapacity' => sprintf("%.01f %%", $totalFactorsInOrders / (float)$factorsLimit * 100)
            ],
            'finishedOrders' => [
                'quantity' => $finishedQuantity,
                'factors' => $finishedFactors,
            ],
            'notFinishedOrders' => [
                'quantity' => $totalQuantity - $finishedQuantity,
                'factors' => sprintf("%.2f", $totalFactorsInOrders - $finishedFactors),
            ]
        ];
    }
}