<?php

namespace App\Module\Reports\Production\DTO;

class AttentionProductionDTO
{
    public function __construct(
        public readonly string $departmentSlug,
        public readonly ?string $status,
        public readonly ?\DateTimeInterface $dateStart,
        public readonly ?\DateTimeInterface $dateEnd,
        public readonly ?int $daysLate = null,
    ) {
    }
}
