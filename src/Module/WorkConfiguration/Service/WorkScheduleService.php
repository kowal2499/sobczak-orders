<?php

namespace App\Module\WorkConfiguration\Service;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use DateTimeImmutable;

class WorkScheduleService
{

    public function __construct(
        private readonly WorkScheduleRepository  $workScheduleRepository,
        private readonly DefaultHolidaysProvider $defaultHolidaysProvider,
    ) {
    }

    /**
     * @param int $year
     * @param ?int $month
     * @return WorkSchedule[]
     */
    public function getFreeDays(int $year, ?int $month): array
    {
        $dateStart = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $year, $month ?? 1));
        if ($month !== null) {
            $dateEnd = $dateStart->modify('last day of this month');
        } else {
            $dateEnd = $dateStart->modify('last day of December');
        }

        $result = [];
        // get holidays
        foreach ([
            $this->getWeekendsInRange($dateStart, $dateEnd),
            $this->getDefaultHolidaysInRange($dateStart, $dateEnd),
            $this->getCustomHolidaysInRange($dateStart, $dateEnd),
        ] as $daysSource) {
            foreach ($daysSource as $day) {
                $result[$day->getDate()->format('Y-m-d')] = $day;
            }
        }

        // remove holidays which are marked as working days in repository
        foreach ($this->getCustomWorkingDaysInRange($dateStart, $dateEnd) as $day) {
            if (isset($result[$day->getDate()->format('Y-m-d')])) {
                unset($result[$day->getDate()->format('Y-m-d')]);
            }
        }

        $result = array_values($result);
        // sort by date
        usort($result, function (WorkSchedule $a, WorkSchedule $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $result;
    }

    /**
     * @param int $year
     * @param ?int $month
     * @return WorkSchedule[]
     */
    public function getWorkingDays(int $year, ?int $month): array
    {
        $dateStart = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $year, $month ?? 1));
        if ($month !== null) {
            $dateEnd = $dateStart->modify('last day of this month');
        } else {
            $dateEnd = $dateStart->modify('last day of December');
        }

        $result = [];
        $holidays = array_map(fn (WorkSchedule $day) => $day->getDate()->format('Y-m-d'), $this->getFreeDays($year, $month));
        $currentDate = clone $dateStart;
        while ($currentDate <= $dateEnd) {
            if (!in_array($currentDate->format('Y-m-d'), $holidays)) {
                $result[] = new WorkSchedule(
                    date: $currentDate,
                    dayType: ScheduleDayType::Working,
                );
            }
            $currentDate = $currentDate->modify('+1 day');
        }

        return $result;
    }

    /**
     * @param DateTimeImmutable $dateStart
     * @param DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    private function getDefaultHolidaysInRange(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
        return $this->defaultHolidaysProvider->getHolidays($dateStart, $dateEnd);
    }

    /**
     * @param DateTimeImmutable $dateStart
     * @param DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    private function getWeekendsInRange(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
        $weekends = [];
        $currentDate = clone $dateStart;
        while ($currentDate <= $dateEnd) {
            if (in_array($currentDate->format('N'), [6, 7])) { // 6 = Saturday, 7 = Sunday
                $weekends[] = new WorkSchedule(
                    date: $currentDate,
                    dayType: ScheduleDayType::Holiday,
                    description: 'weekend',
                );
            }
            $currentDate = $currentDate->modify('+1 day');
        }
        return $weekends;
    }

    /**
     * @param DateTimeImmutable $dateStart
     * @param DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    private function getCustomHolidaysInRange(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
        return $this->workScheduleRepository->findHolidaysByRange($dateStart, $dateEnd) ?? [];
    }

    /**
     * @param DateTimeImmutable $dateStart
     * @param DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    private function getCustomWorkingDaysInRange(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
        return $this->workScheduleRepository->findWorkingDaysByRange($dateStart, $dateEnd) ?? [];
    }
}