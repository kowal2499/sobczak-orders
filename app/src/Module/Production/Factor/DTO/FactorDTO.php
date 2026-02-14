<?php

namespace App\Module\Production\Factor\DTO;

use App\Module\Production\Entity\FactorSource;

class FactorDTO
{
    public FactorSource $source;
    public float $value = 0;
    public ?int $agreementLineId;
    public ?string $departmentSlug;
    public ?string $description;

    public function __construct(
        FactorSource $source,
        float $value = 0,
        ?int $agreementLineId = null,
        ?string $departmentSlug = null,
        ?string $description = null
    ) {
        $this->source            = $source;
        $this->value             = $value;
        $this->agreementLineId   = $agreementLineId;
        $this->departmentSlug    = $departmentSlug;
        $this->description       = $description;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            FactorSource::from($data['source']),
            $data['value'] ?? 0,
            $data['agreementLineId'] ?? null,
            $data['departmentSlug'] ?? null,
            $data['description'] ?? null
        );
    }
}
