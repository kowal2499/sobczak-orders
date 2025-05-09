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

        $grants = $this->merge(
            $this->getGransFromRoles($userRoles),
            $this->getGrantsFromUser($user)
        );

        // remove falsy grants
        $grants = array_filter($grants, fn($value) => $value);
        // get only slugs
        return array_values($grants);
    }

    /**
     * @param AuthUserRole[] $userRoles
     * @return array
     */
    public function getGransFromRoles(array $userRoles): array
    {
        $result = [];
        foreach ($userRoles as $userRole) {
            $grantValues = $this->roleGrantValueRepository->findAllByRole($userRole->getRole());
            foreach ($grantValues as $grantValue) {
                $result[$grantValue->getGrantVO()->toString()] = (bool)$grantValue->getValue();
            }
        }
        return $result;
    }

    public function getGrantsFromUser(User $user): array
    {
        $result = [];
        foreach ($this->userGrantValueRepository->findAllByUser($user) as $grantValue) {
            $result[$grantValue->getGrantVO()->toString()] = (bool)$grantValue->getValue();
        }
        return $result;
    }

    public function merge(...$grantsPool): array
    {
        $result = [];
        foreach ($grantsPool as $pool) {
            foreach ($pool as $grantSlug => $grantValue) {
                $result[$grantSlug] = (bool) $grantValue;
            }
        }
        return $result;
    }
}