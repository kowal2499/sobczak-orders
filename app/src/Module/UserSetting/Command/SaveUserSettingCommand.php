<?php

namespace App\Module\UserSetting\Command;

use Symfony\Component\Validator\Constraints as Assert;

class SaveUserSettingCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $userId,
        #[Assert\NotBlank]
        public readonly string $context,
        #[Assert\Type('array')]
        public readonly array $data,
    ) {
    }
}
