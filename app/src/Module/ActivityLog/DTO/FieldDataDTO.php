<?php

namespace App\Module\ActivityLog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class FieldDataDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $value,
    ) {
    }
}
