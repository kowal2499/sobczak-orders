<?php

namespace App\Module\Reports\Production\DTO;

use App\Module\AgreementLine\Entity\AgreementLineRM;

class CapacityBurnoutDTO
{
    public function __construct(
        public ?\DateTimeImmutable $start = null,
        public ?\DateTimeImmutable $end = null,
        public ?float $capacity = null,
        public ?float $capacityBurned = null,
        /** @var AgreementLineRM[] */
        public array $agreementLines = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start?->format('Y-m-d'),
            'end' => $this->end?->format('Y-m-d'),
            'capacity' => $this->capacity,
            'capacityBurned' => $this->capacityBurned,
            'agreementLines' => array_map(function (AgreementLineRM $agreementLine) {
                return [
                    'id' => $agreementLine->getAgreementId(),
                    'name' => $agreementLine->getCustomerName(),
                ];
            }, $this->agreementLines)
        ];
    }
}