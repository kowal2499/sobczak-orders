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
        return [];
//        return [
//            'id' => $user->getId(),
//            'email' => $user->getEmail(),
//            'global' => $this->getRoleGrants($this->userRoleRepository->findAllByUser($user)),
//            'local' => $this->getUserGrants($user),
//        ];
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
                if ($grantValue->getValue() !== true) {
                    continue;
                }
                $result[] = $grantValue->getGrantVO()->toString();
            }
        }
        return $result;
    }

    public function getUserGrants(User $user): array
    {
        $result = [];
        foreach ($this->userGrantValueRepository->findAllByUser($user) as $grantValue) {
            if ($grantValue->getValue() !== true) {
                continue;
            }
            $result[] = $grantValue->getGrantVO()->toString();
        }
        return $result;
    }

    public function mergeGrants($globalGrants, $localGrants): array
    {
        return $globalGrants;
    }
}