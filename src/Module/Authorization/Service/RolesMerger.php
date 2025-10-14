<?php

namespace App\Module\Authorization\Service;

use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;

class RolesMerger
{
    public function __construct(
        protected readonly AuthRoleGrantValueRepositoryInterface $roleGrantValueRepository,
        protected readonly GrantValueSupplier                    $grantValueSupplier,
    ) {}

    /**
     * @param AuthRole ...$roles
     * @return AuthRoleGrantValue[]
     */
    public function merge(AuthRole ...$roles): array
    {
        $result = [];
        foreach ($roles as $role) {
            $grantValues = $this->roleGrantValueRepository->findAllByRole($role);
            foreach ($grantValues as $grantValue) {
                $value = $this->grantValueSupplier->getValue($grantValue);
                // keep only true values from roles, false values should be set only by user grants
                if ($value) {
                    $result[$grantValue->getGrantVO()->toString()] = $grantValue;
                }
            }
        }
        return array_values($result);
    }
}