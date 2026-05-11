<?php

namespace App\Module\Task\Command;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteTaskCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $taskId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $userId,
    ) {
    }
}
