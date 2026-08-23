<?php

namespace App\Module\BonusSettlement\DTO;

use App\Entity\User;

/**
 * Jedna przynależność: pracownik plus dział, z którego przysługuje mu premia.
 * Pracownik z grantami do kilku działów ma tyle obiektów, ile działów.
 */
class DepartmentMembershipDTO
{
    public function __construct(
        public readonly User $user,
        public readonly string $userLabel,
        public readonly string $departmentSlug,
        public readonly string $departmentLabel,
    ) {
    }
}
