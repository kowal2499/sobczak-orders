<?php

namespace App\Module\Task\Command;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateTaskCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $taskId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $userId,
        #[Assert\Type('string')]
        public readonly ?string $dateStart = null,
        #[Assert\Type('string')]
        public readonly ?string $dateEnd = null,
        #[Assert\NotBlank]
        #[Assert\Choice([10, 11, 12])]
        public readonly int $status,
        #[Assert\Type('string')]
        public readonly ?string $title = null,
        #[Assert\Type('string')]
        public readonly ?string $description = null,
    ) {
    }
}
