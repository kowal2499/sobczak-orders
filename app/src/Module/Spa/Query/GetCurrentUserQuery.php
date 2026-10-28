<?php

namespace App\Module\Spa\Query;

use App\Entity\User;

class GetCurrentUserQuery
{
    public function __construct(
        public readonly User $user,
        public readonly string $locale,
        public readonly bool $isImpersonated,
    ) {
    }
}
