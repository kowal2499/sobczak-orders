<?php

namespace App\Module\ApiToken\DTO;

use App\Module\ApiToken\Entity\ApiToken;

class IssuedApiTokenDTO
{
    public function __construct(
        public readonly ApiToken $token,
        public readonly string $plainToken,
    ) {
    }
}
