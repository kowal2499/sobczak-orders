<?php

namespace App\Module\Production\DTO;

use App\Module\Production\Entity\FactorSource;

class FactorRatioDTO
{
    public function __construct(
        private readonly FactorSource $factorSource,
        private readonly float $value,
        private readonly ?int $id,
        private readonly ?string $departmentSlug,
        private readonly ?string $description,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartmentSlug(): ?string
    {
        return $this->departmentSlug;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFactorSource(): FactorSource
    {
        return $this->factorSource;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            FactorSource::from($data['source']),
            $data['value'],
        $data['id'] ?? null,
            $data['departmentSlug'] ?? null,
            $data['description'] ?? null,
        );
    }
}