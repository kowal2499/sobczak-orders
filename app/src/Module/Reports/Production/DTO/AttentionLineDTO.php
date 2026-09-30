<?php

namespace App\Module\Reports\Production\DTO;

class AttentionLineDTO
{
    /**
     * @param AttentionProductionDTO[] $productions
     */
    public function __construct(
        public readonly int $agreementLineId,
        public readonly string $orderNumber,
        public readonly string $customerName,
        public readonly ?string $productName,
        public readonly ?int $status,
        public readonly \DateTimeInterface $agreementCreateDate,
        public readonly \DateTimeInterface $confirmedDate,
        public readonly int $days,
        public readonly array $productions = [],
    ) {
    }
}
