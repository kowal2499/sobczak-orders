<?php

namespace App\Module\AgreementLine\Entity;

class TagRM
{
    public function __construct(
        private readonly string $name,
        private readonly string $icon,
        private readonly string $color,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['icon'],
            $data['color'],
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
