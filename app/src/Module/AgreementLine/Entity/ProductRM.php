<?php

namespace App\Module\AgreementLine\Entity;

class ProductRM
{
    private int $id;
    private string $name;
    private float $factor;

    public function __construct(
        int $id,
        string $name,
        float $factor
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->factor = $factor;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'factor' => $this->factor,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            factor: $data['factor'],
        );
    }
}
