<?php

namespace App\Module\Authorization\ValueObject;

class GrantVO
{
    private string $slug;
    private ?string $optionSlug;
    private bool $value;

    private function __construct(string $grantString)
    {
        // get value and remove from it from string
        $pattern = '/=(.*)$/';
        if (preg_match($pattern, $grantString, $matches)) {
            $this->value = ($matches[1] === 'true');
            $grantString = preg_replace($pattern, '', $grantString);
        } else {
            $this->value = true; // default value if not provided
        }

        // get grant slug parts
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getOptionSlug(): ?string
    {
        return $this->optionSlug;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function toString(): string
    {
        return implode(':', array_filter([$this->getSlug(), $this->getOptionSlug()]));
    }


}