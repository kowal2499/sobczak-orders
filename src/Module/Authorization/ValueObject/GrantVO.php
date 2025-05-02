<?php

namespace App\Module\Authorization\ValueObject;

class GrantVO
{
    private string $slug;
    private ?string $optionValue;
    private bool $value;

    private function __construct(string $grantString)
    {
        if (!str_contains($grantString, ':') && str_contains($grantString, '=')) {
            $grantString = str_replace('=', ':=', $grantString);
        }
        $grantParts = explode('|', str_replace([':', '='], '|', $grantString));
        if (!$grantParts[0]) {
            throw new \RuntimeException('Grant slug not found');
        }
        $this->slug = $grantParts[0];
        $this->optionValue = $grantParts[1] ?? null;
        $this->value = !isset($grantParts[2]) || $grantParts[2] === 'true';
    }

    public static function fromString(string $grantString): self
    {
        return new self($grantString);
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getOptionValue(): string
    {
        return $this->optionValue;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}