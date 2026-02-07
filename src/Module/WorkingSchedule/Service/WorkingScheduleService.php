<?php

namespace App\Module\WorkingSchedule\Service;

use App\Module\WorkingSchedule\Repository\WorkingScheduleRepository;
use App\Module\WorkingSchedule\ValueObject\ScheduleDay;
use App\Module\WorkingSchedule\ValueObject\ScheduleDayType;

class WorkingScheduleService
{

    public function __construct(
        private readonly WorkingScheduleRepository $workingScheduleRepository,
        private readonly DefaultHolidaysProvider $defaultHolidaysProvider,
    ) {
    }

    public function getSchedule(int $year, ?int $month, ?ScheduleDayType $dayType = null): array
    {
        $dateStart = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $year, $month ?? 1));
        if ($month !== null) {
            $dateEnd = $dateStart->modify('last day of this month');
        } else {
            $dateEnd = $dateStart->modify('last day of December');
        }

        $result = [];
        foreach ([
            $this->getWeekendsInRange($dateStart, $dateEnd),
            $this->getDefaultHolidaysInRange($dateStart, $dateEnd),
            $this->getCustomHolidaysInRange($dateStart, $dateEnd),
        ] as $daysSource) {
            foreach ($daysSource as $day) {
                $result[$day->getDate()->format('Y-m-d')] = $day;
            }
        }
        $result = array_values($result);
        // sort by date
        usort($result, function (ScheduleDay $a, ScheduleDay $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $result;

    }

    private function getDefaultHolidaysInRange(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        return $this->defaultHolidaysProvider->getHolidays($dateStart, $dateEnd);
    }

    private function getWeekendsInRange(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd)
    {
        $weekends = [];
        $currentDate = clone $dateStart;
        while ($currentDate <= $dateEnd) {
            if (in_array($currentDate->format('N'), [6, 7])) { // 6 = Saturday, 7 = Sunday
                $weekends[] = new ScheduleDay(
                    date: $currentDate,
                    dayType: ScheduleDayType::Holiday,
                    description: 'weekend',
                );
            }
            $currentDate = $currentDate->modify('+1 day');
        }
        return $weekends;

    }

    private function getCustomHolidaysInRange(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        return [];
    }
}