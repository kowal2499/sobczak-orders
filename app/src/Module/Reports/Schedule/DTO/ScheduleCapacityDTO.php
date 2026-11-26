<?php

namespace App\Module\Reports\Schedule\DTO;

use App\Module\Agreement\ReadModel\AgreementLineRM;

class ScheduleCapacityDTO
{
    /**
     * @param AgreementLineRM[] $agreementLines lines delivered in the week of $date (shared by all its days)
     */
    public function __construct(
        public ?\DateTimeImmutable $date = null,
        public ?float $capacity = null,
        public ?float $capacityBurned = null,
        public array $agreementLines = [],
        public ?\DateTimeImmutable $weekStart = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date?->format('Y-m-d'),
            'weekStart' => $this->weekStart?->format('Y-m-d'),
            'capacity' => $this->capacity,
            'capacityBurned' => $this->capacityBurned,
        ];
    }
}
