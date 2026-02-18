<?php

namespace App\Module\Reports\Production\Service;

use App\Entity\AgreementLine;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use App\Module\Reports\Production\DTO\CapacityBurnoutDTO;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;

class ProductionCapacityBurnoutService
{

    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
        private readonly WorkCapacityRepository $workCapacityRepo,
        private readonly WorkScheduleRepository $workScheduleRepo,
    ) {
    }

    public function calculateBurnout(\DateTimeImmutable $start, \DateTimeImmutable $end): CapacityBurnoutDTO
    {
        $agreementLines = $this->agreementLineRepo->search([
            'search' => [
                'hasProduction' => true,
                'dateDelivery' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ],
                'status' => [
                    AgreementLine::STATUS_WAREHOUSE,
                    AgreementLine::STATUS_MANUFACTURING,
                    AgreementLine::STATUS_ARCHIVED
                ],
                'sort' => 'dateConfirmed_ASC',
            ],
        ])->getResult();

        $capacities = $this->getCapacities($start, $end);
        $holidays = $this->getFreeDays($start, $end);

        // Dla każdego dnia znajdź odpowiednie WorkCapacity
        $totalCapacity = 0.0;
        $traveller = clone $start;
        while ($traveller <= $end) {
            $dateKey = $traveller->format('Y-m-d');

            // Pomijamy święta - nie zwiększamy capacity
            if (!isset($holidays[$dateKey])) {
                $dayCapacity = $this->findCapacityForDay($traveller, $capacities);
                $totalCapacity += $dayCapacity->getCapacity();
            }

            $traveller = $traveller->modify('+1 day');
        }


        return new CapacityBurnoutDTO(
            $start,
            $end,
            $totalCapacity,
            $this->getCapacityBurned($agreementLines),
            $agreementLines,
        );
    }

    /**
     * @param AgreementLineRM[] $agreementLines
     * @return float
     */
    private function getCapacityBurned(array $agreementLines): float
    {
        return array_reduce(
            $agreementLines,
            fn(float $carry, AgreementLineRM $agreementLine) => $carry + $agreementLine->getFactor(),
            0.0
        );
    }

    /**
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return WorkCapacity[]
     */
    private function getCapacities(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->workCapacityRepo->findByRange($start, $end);
    }

    /**
     * Znajduje najbliższe WorkCapacity dla danego dnia
     *
     * @param \DateTimeImmutable $day
     * @param WorkCapacity[] $capacities Lista WorkCapacity posortowana ASC według dateFrom
     * @return WorkCapacity
     */
    private function findCapacityForDay(\DateTimeImmutable $day, array $capacities): WorkCapacity
    {
        $result = new WorkCapacity($day, WorkCapacity::DEFAULT_CAPACITY);

        // Capacities są posortowane ASC, szukamy najbliższego wcześniejszego lub równego
        foreach ($capacities as $capacity) {
            // Jeśli dateFrom jest wcześniejsze lub równe dniowi, to jest kandydatem
            if ($capacity->getDateFrom() <= $day) {
                $result = $capacity;
            } else {
                // Jeśli dateFrom jest późniejsze niż dzień, przerywamy - mamy już najlepszy wynik
                break;
            }
        }

        return $result;
    }

    /**
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return array<string, true> Tablica dat w formacie Y-m-d jako klucze
     */
    private function getFreeDays(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $holidays = $this->workScheduleRepo->findHolidaysByRange(
            \DateTimeImmutable::createFromInterface($start),
            \DateTimeImmutable::createFromInterface($end)
        );

        // Konwertuj do tablicy asocjacyjnej dla szybkiego sprawdzania
        $result = [];
        foreach ($holidays as $holiday) {
            $result[$holiday->getDate()->format('Y-m-d')] = true;
        }

        return $result;
    }
}