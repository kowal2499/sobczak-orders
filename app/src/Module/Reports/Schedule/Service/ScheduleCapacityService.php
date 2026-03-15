<?php

namespace App\Module\Reports\Schedule\Service;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use App\Module\WorkConfiguration\Service\WorkScheduleService;

class ScheduleCapacityService
{
    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
        private readonly WorkCapacityRepository $workCapacityRepo,
        private readonly WorkScheduleService $workScheduleService
    ) {
    }

    /**
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @return ScheduleCapacityDTO[]
     */
    public function calculateBurnout(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        /**
            1. Wyznacz granice zakresów, od pierwszego dnia tygodnia (poniedziałek) zawierającego $start,
               do ostatniego dnia tygodnia (niedziela) zawierającego $end
            2. Pobierz wszystkie AgreementLineRM, których dateDelivery mieści się w tym zakresie
               i które nie są DELETED (ustalić z klientem, czy STATUS_WAITING też ma być pominięty)
            3. Pobierz wszystkie WorkCapacity, które obowiązywały w tym zakresie
            4. Pobierz wszystkie wolne dni w tym zakresie
            5. Iteruj po każdym dniu w ramach każdego tygodnia w zakresie, sumując capacity,
               jeśli nie jest to wolny dzień. W ten sposób uzyskujemy capcity w ujęciu tygodniowym.
            6. Iteruj po każdym dniu w ramach argumentów funkcji i przypisz dla dnia capacity z jego tygodnia.
        */

        $rangeStart = $this->getLastMonday($start);
        $rangeEnd = $this->getNextSunday($end);

        $holidays = $this->getFreeDays($rangeStart, $rangeEnd);
        $capacities = $this->getCapacities($rangeStart, $rangeEnd);
        $allAgreementLines = $this->getAgreementLineRMs($rangeStart, $rangeEnd);
        $result = [];

        $weekRunner = clone $rangeStart;
        while ($weekRunner <= $rangeEnd) {
            $weekEnd = $this->getNextSunday($weekRunner);
            $agreementLines = $this->filterAgreementLinesByWeek($allAgreementLines, $weekRunner, $weekEnd);

            $weekCapacity = $this->getCapacityByRange($weekRunner, $weekEnd, $holidays, $capacities);
            $weekCapacityBurned = $this->getCapacityBurned($agreementLines);

            $dayRunner = clone $weekRunner;
            while ($dayRunner <= $weekEnd) {
                $dateKey = $dayRunner->format('Y-m-d');
                if ($dayRunner >= $start && $dayRunner <= $end && !isset($holidays[$dateKey])) {
                    $result[] = new ScheduleCapacityDTO(
                        $dayRunner,
                        $weekCapacity,
                        $weekCapacityBurned,
                        $agreementLines,
                    );
                }
                $dayRunner = $dayRunner->modify('+1 day');
            }
            $weekRunner = $weekRunner->modify('+1 week');
        }

        return $result;
    }

    /**
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param array<string, true> $holidays
     * @param WorkCapacity[] $capacities
     * @return float
     */
    private function getCapacityByRange(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $holidays,
        array $capacities
    ): float {
        $capacitySum = 0.0;
        $dayRunner = clone $start;
        while ($dayRunner <= $end) {
            $dateKey = $dayRunner->format('Y-m-d');
            // Pomijamy święta - nie zwiększamy capacity
            if (!isset($holidays[$dateKey])) {
                $dayCapacity = $this->findCapacityForDay($dayRunner, $capacities);
                $capacitySum += $dayCapacity->getCapacity();
            }
            $dayRunner = $dayRunner->modify('+1 day');
        }
        return $capacitySum;
    }

    private function getLastMonday(\DateTimeImmutable $date): \DateTimeImmutable
    {
        if ((int) $date->format('N') === 1) {
            return $date;
        }
        return $date->modify('monday this week');
    }

    private function getNextSunday(\DateTimeImmutable $date): \DateTimeImmutable
    {
        if ((int) $date->format('N') === 7) {
            return $date;
        }
        return $date->modify('sunday this week');
    }

    private function getAgreementLineRMs(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->agreementLineRepo->search([
            'search' => [
                'hasProduction' => true,
                'dateDelivery' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ],
                'statusNot' => [ AgreementLine::STATUS_DELETED, ],
                'sort' => 'dateConfirmed_ASC',
            ],
        ])->getResult();
    }

    /**
     * Filtruje AgreementLines dla danego tygodnia na podstawie confirmedDate
     *
     * @param AgreementLineRM[] $agreementLines
     * @param \DateTimeImmutable $weekStart
     * @param \DateTimeImmutable $weekEnd
     * @return AgreementLineRM[]
     */
    private function filterAgreementLinesByWeek(
        array $agreementLines,
        \DateTimeImmutable $weekStart,
        \DateTimeImmutable $weekEnd
    ): array {
        return array_filter($agreementLines, function (AgreementLineRM $agreementLine) use ($weekStart, $weekEnd) {
            $confirmedDate = $agreementLine->getConfirmedDate();

            if ($confirmedDate === null) {
                return false;
            }

            // Normalizuj daty do porównania (bez czasu)
            $confirmed = \DateTimeImmutable::createFromInterface($confirmedDate)->setTime(0, 0, 0);
            $start = $weekStart->setTime(0, 0, 0);
            $end = $weekEnd->setTime(0, 0, 0);

            return $confirmed >= $start && $confirmed <= $end;
        });
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
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @return array<string, true> Tablica dat w formacie Y-m-d jako klucze
     */
    private function getFreeDays(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $holidays = $this->workScheduleService->getFreeDays($start, $end);

        // Konwertuj do tablicy asocjacyjnej dla szybkiego sprawdzania
        $result = [];
        foreach ($holidays as $holiday) {
            $result[$holiday->getDate()->format('Y-m-d')] = true;
        }

        return $result;
    }
}
