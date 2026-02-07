<?php

namespace App\Module\WorkingSchedule\Service;

use App\Module\WorkingSchedule\ValueObject\ScheduleDay;
use App\Module\WorkingSchedule\ValueObject\ScheduleDayType;
use Carbon\Carbon;
use Spatie\Holidays\Holidays;

class DefaultHolidaysProvider
{
    /**
     * @param \DateTimeImmutable $dateStart
     * @param \DateTimeImmutable $dateEnd
     * @return ScheduleDay[]
     */
    public function getHolidays(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        $yearStart = (int)$dateStart->format('Y');
        $yearEnd = (int)$dateEnd->format('Y');
        $holidays = [];
        for ($year = $yearStart; $year <= $yearEnd; $year++) {
            $result = Holidays::for(country: 'pl', year: $year)->get();
            $holidays = array_merge($holidays, $result);
        }

        $holidaysInRange = array_filter(
            $holidays,
            function (array $holiday) use ($dateStart, $dateEnd) {
                $rawDate = $holiday['date'];
                return $rawDate >= $dateStart && $rawDate <= $dateEnd;
            }
        );

        $mapped = array_map(
            fn (array $holiday) => new ScheduleDay(
                date: $holiday['date']->toDateTimeImmutable(),
                dayType: ScheduleDayType::Holiday,
                description: $holiday['name'] ?? '',
            ),
            $holidaysInRange
        );

        return array_values($mapped);
    }
}