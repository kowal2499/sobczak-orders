<?php

namespace App\Module\WorkConfiguration\Service;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use Spatie\Holidays\Holidays;

class DefaultHolidaysProvider
{
    /**
     * @param \DateTimeImmutable $dateStart
     * @param \DateTimeImmutable $dateEnd
     * @return WorkSchedule[]
     */
    public function getHolidays(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        $yearStart = (int) $dateStart->format('Y');
        $yearEnd = (int) $dateEnd->format('Y');
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
            fn (array $holiday) => new WorkSchedule(
                $holiday['date'],
                ScheduleDayType::Holiday,
                $holiday['name']
            ),
            $holidaysInRange
        );

        return array_values($mapped);
    }
}
