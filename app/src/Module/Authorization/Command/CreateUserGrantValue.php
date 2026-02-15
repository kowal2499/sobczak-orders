<?php

namespace App\Module\Authorization\Command;

class CreateUserGrantValue
{
    public function __construct(
        private readonly int $userId,
        private readonly int $grantId,
        private readonly ?string $grantOptionSlug,
        private readonly bool $value
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getGrantId(): int
    {
        return $this->grantId;
    }

    public function getGrantOptionSlug(): ?string
    {
        return $this->grantOptionSlug;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
