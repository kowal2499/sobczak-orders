<?php

namespace App\Module\Spa\QueryHandler;

use App\Entity\Customer;
use App\Module\Authorization\Service\GrantsResolver;
use App\Module\Spa\Query\GetCurrentUserQuery;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class GetCurrentUserQueryHandler
{
    public function __construct(
        private readonly GrantsResolver $grantsResolver,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(GetCurrentUserQuery $query): array
    {
        $user = $query->user;

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'locale' => $query->locale,
            'roles' => array_values(array_unique(
                $this->roleHierarchy->getReachableRoleNames($user->getRoles())
            )),
            'grants' => array_values($this->grantsResolver->getGrants($user)),
            'customerIds' => array_values(array_filter(
                $user->getCustomers()->map(fn (?Customer $c) => $c?->getId())->toArray()
            )),
            'isImpersonated' => $query->isImpersonated,
        ];
    }
}
