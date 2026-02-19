<?php

namespace App\Module\Reports\Schedule\DTO;

use App\Module\AgreementLine\Entity\AgreementLineRM;

class ScheduleCapacityDTO
{
    public function __construct(
        public ?\DateTimeImmutable $date = null,
        public ?float $capacity = null,
        public ?float $capacityBurned = null,
        /** @var AgreementLineRM[] */
        public array $agreementLines = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date?->format('Y-m-d'),
            'capacity' => $this->capacity,
            'capacityBurned' => $this->capacityBurned,
            'agreementLines' => array_map(function (AgreementLineRM $agreementLine) {
                return $agreementLine->toArray();
            }, $this->agreementLines)
        ];
    }
}