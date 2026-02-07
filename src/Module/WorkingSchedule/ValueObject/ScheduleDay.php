<?php

namespace App\Module\WorkingSchedule\ValueObject;

class ScheduleDay
{
    public function __construct(
        private readonly \DateTimeImmutable $date,
        private readonly ScheduleDayType $dayType,
        private readonly string $description = ''
    ) {
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getDayType(): ScheduleDayType
    {
        return $this->dayType;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
