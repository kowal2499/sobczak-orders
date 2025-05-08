<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;

class GrantsResolver
{
    public function __construct(
        private readonly AuthRoleGrantValueRepositoryInterface   $roleGrantValueRepository,
        private readonly AuthUserGrantValueRepositoryInterface   $userGrantValueRepository,
        private readonly AuthUserRoleRepositoryInterface         $userRoleRepository,
    ) {
    }

    public function resolve(User $user): array
    {
        $userRoles = $this->userRoleRepository->findAllByUser($user);
        $roleGrants = $this->getRoleGrants($userRoles);
        $userGrants = $this->getUserGrants($user);
        if (!$roleGrants && !$userGrants) {
            return [];
        }

        return array_map(
            fn($roleSlugValue) => $roleSlugValue['slug'],
            array_filter([...$roleGrants, ...$userGrants], fn($roleSlugValue) => $roleSlugValue['value'])
        );
    }

    /**
     * @param AuthUserRole[] $roles
     * @return array
     */
    public function getRoleGrants(array $roles): array
    {
        $result = [];
        foreach ($roles as $userRole) {

            $grantValues = $this->roleGrantValueRepository->findAllByRole($userRole->getRole());
            foreach ($grantValues as $grantValue) {
                $result[] = [
                    'slug' => $grantValue->getGrantVO()->toString(),
                    'value' => (bool)$grantValue->getValue()
                ];
            }
        }
        return $result;
    }

    public function getUserGrants(User $user): array
    {
        $result = [];
        foreach ($this->userGrantValueRepository->findAllByUser($user) as $grantValue) {
            $result[] = [
                'slug' => $grantValue->getGrantVO()->toString(),
                'value' => (bool)$grantValue->getValue()
            ];
        }
        return $result;
    }

    public function add($globalGrants, $localGrants): array
    {
        return $globalGrants;
    }
}