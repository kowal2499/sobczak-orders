<?php

namespace App\Module\ActivityLog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class FieldFilter
{
    /**
     * @param string[]|null $values
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        public readonly ?string $value = null,
        public readonly ?array $values = null,
    ) {
    }

    #[Assert\IsTrue(message: 'Either "value" or "values" must be provided.')]
    public function hasValueOrValues(): bool
    {
        return $this->value !== null || ($this->values !== null && $this->values !== []);
    }
}
