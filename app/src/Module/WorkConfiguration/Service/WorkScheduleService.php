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
     * @param \DateTimeInterface $dateStart
     * @param \DateTimeInterface $dateEnd
     * @return WorkSchedule[]
     */
    public function getFreeDays(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
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
     * @param DateTimeImmutable $dateStart
     * @param DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    public function getWorkingDays(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): array
    {
        $result = [];
        $holidays = array_map(fn (WorkSchedule $day) => $day->getDate()->format('Y-m-d'), $this->getFreeDays($dateStart, $dateEnd));
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
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @param ScheduleDayType $type
     * @return WorkSchedule[]
     */
    public function getDays(DateTimeImmutable $startDate, DateTimeImmutable $endDate, ScheduleDayType $type): array
    {
        if ($type === ScheduleDayType::Working) {
            return $this->getWorkingDays($startDate, $endDate);
        }

        if ($type === ScheduleDayType::Holiday) {
            return $this->getFreeDays($startDate, $endDate);
        }

        throw new \InvalidArgumentException('Invalid ScheduleDayType provided.');
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