<?php

namespace App\Module\Product\Command;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $productId,
        #[Assert\NotBlank(message: 'Nazwa nie może być pusta')]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        #[Assert\NotNull(message: 'Wartość musi być liczbą większą od 0')]
        #[Assert\PositiveOrZero(message: 'Wartość nie może być niższa od 0')]
        public readonly ?float $factor,
        public readonly ?string $description = null,
    ) {
    }
}
