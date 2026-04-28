<?php

namespace App\Module\Production\Service\ProductionDateStrategy;

class DateShifter
{
    private const WEEKDAYS = [
        'niedziela' => 0,
        'poniedziałek' => 1,
        'wtorek' => 2,
        'środa' => 3,
        'czwartek' => 4,
        'piątek' => 5,
        'sobota' => 6,
    ];

    public function shiftByDays(\DateTimeImmutable $date, int $count, string $direction): \DateTimeImmutable
    {
        $modifier = ($direction === 'before' ? '-' : '+') . $count . ' day';
        return $date->modify($modifier);
    }

    public function shiftByWeekday(\DateTimeImmutable $date, string $weekday, string $direction): \DateTimeImmutable
    {
        if (!isset(self::WEEKDAYS[strtolower($weekday)])) {
            throw new \InvalidArgumentException("Invalid weekday name: $weekday");
        }
        $target = self::WEEKDAYS[strtolower($weekday)];
        $current = (int) $date->format('w');
        if ($current === $target) {
            return $date;
        }
        $delta = $direction === 'before'
            ? ($current - $target + 7) % 7
            : ($target - $current + 7) % 7;
        $modifier = ($direction === 'before' ? '-' : '+') . $delta . ' day';
        return $date->modify($modifier);
    }
}
