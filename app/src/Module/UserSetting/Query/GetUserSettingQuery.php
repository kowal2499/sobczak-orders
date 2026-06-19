<?php

namespace App\Module\UserSetting\Query;

final class GetUserSettingQuery
{
    public function __construct(
        public readonly int $userId,
        public readonly string $context,
    ) {
    }
}
