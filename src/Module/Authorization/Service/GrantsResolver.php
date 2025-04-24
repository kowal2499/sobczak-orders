<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\Authorization\ValueObject\GrantType;

class GrantsResolver
{
    public function __construct(
        private readonly AuthRoleGrantValueRepository   $roleGrantValueRepository,
        private readonly AuthUserRoleRepository         $userRoleRepository,
    ) {
    }

    public function resolve(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'global' => $this->getGlobalGrants($user)
        ];
    }

    public function getGlobalGrants(User $user): array
    {
        $result = [];

        foreach ($this->userRoleRepository->findAllByUser($user) as $userRole) {
            $grantValues = $this->roleGrantValueRepository->findAllByRole($userRole->getRole());
            foreach ($grantValues as $grantValue) {
                $grant = $grantValue->getGrant();

                if ($grant->getType() === GrantType::Boolean
                    && $grantValue->getValue()->getRawValue() === true)
                {
                    $result[] = $grant->getSlug();
                } elseif ($grant->getType() === GrantType::Select) {
                    foreach ($grantValue->getValue()->getRawValue() as $val) {
                        $result[] = $grant->getSlug() . ':' . $val;
                    }
                }
            }
        }

        return $result;
    }

    public function getLocalGrants(): array
    {
        return [];
    }

    public function mergeGrants($globalGrants, $localGrants): array
    {
        return $globalGrants;
    }
}