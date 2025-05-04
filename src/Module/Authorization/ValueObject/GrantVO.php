<?php

namespace App\Module\Authorization\ValueObject;

class GrantVO
{
    private string $slug;
    private ?string $optionSlug;

    private function __construct(string $grantString)
    {

        $grantParts = explode(':', $grantString);
        if (!$grantParts[0]) {
            throw new \RuntimeException('Grant slug not found');
        }
        $this->slug = $grantParts[0];
        $this->optionSlug = $grantParts[1] ?? null;
    }

    public static function m(string $grantString): self
    {
        return new self($grantString);
    }

    public function getBaseSlug(): string
    {
        return $this->slug;
    }

    public function getOptionSlug(): ?string
    {
        return $this->optionSlug;
    }

    public function toString(): string
    {
        return implode(':', array_filter([$this->getBaseSlug(), $this->getOptionSlug()]));
    }
}