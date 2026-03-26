<?php

namespace App\Module\Task\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTaskCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $agreementLineId,

        #[Assert\Type('string')]
        public readonly ?string $dateStart = null,

        #[Assert\Type('string')]
        public readonly ?string $dateEnd = null,

        #[Assert\NotBlank]
        #[Assert\Choice([10, 11, 12])]
        public readonly int $status,

        #[Assert\NotBlank]
        #[Assert\Choice(['task_custom', 'task_confirm_realization_date'])]
        public readonly string $type,

        #[Assert\Type('string')]
        public readonly ?string $title = null,

        #[Assert\Type('string')]
        public readonly ?string $description = null,

        #[Assert\Positive]
        public readonly ?int $ownerId = null,
    ) {
    }
}
