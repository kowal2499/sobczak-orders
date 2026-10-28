<?php

namespace App\Module\Product\DTO;

use App\Entity\Product;

class ProductDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $factor,
    ) {
    }

    public static function fromEntity(Product $product): self
    {
        return new self(
            $product->getId(),
            (string) $product->getName(),
            $product->getDescription(),
            (float) $product->getFactor(),
        );
    }
}
