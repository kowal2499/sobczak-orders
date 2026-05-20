<?php

namespace App\Module\ActivityLog\Command;

use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use Symfony\Component\Validator\Constraints as Assert;

final class AddActivityLogCommand
{
    public const IMPERSONATE_KEY = 'impersonateUserId';

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public readonly string $message,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $type,
        #[Assert\Type('array')]
        public readonly array $contextData = [],
        public readonly ?LogLevel $level = null,
        #[Assert\Positive]
        public readonly ?int $authorUserId = null,
        public readonly ?\DateTimeInterface $createdDate = null,
        public readonly LogPriority $priority = LogPriority::normal,
    ) {
    }
}
