<?php

namespace App\Module\Authorization\CommandHandler;

use App\Module\Authorization\Command\CreateRoleGrantValue;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Service\AuthCacheService;
use InvalidArgumentException;

class CreateRoleGrantValueHandler
{
    public function __construct(
        private readonly AuthRoleRepository $roleRepository,
        private readonly AuthGrantRepository $grantRepository,
        private readonly AuthRoleGrantValueRepository $roleGrantValueRepository,
        private readonly AuthCacheService $cacheService,
    ) {
    }

    public function __invoke(CreateRoleGrantValue $command): void
    {
        $role = $this->roleRepository->find($command->getRoleId());
        $grant = $this->grantRepository->find($command->getGrantId());
        if (!$role || !$grant) {
            throw new InvalidArgumentException('Role or Grant not found');
        }
        $roleGrantValue = $this->roleGrantValueRepository->findOneByRoleAndGrant(
            $role,
            $grant,
            $command->getGrantOptionSlug()
        );
        if (!$roleGrantValue) {
            $roleGrantValue = new AuthRoleGrantValue(
                $role,
                $grant,
                $command->getGrantOptionSlug()
            );
        }
        $roleGrantValue->setValue($command->getValue());

        $this->roleGrantValueRepository->add($roleGrantValue);

        // clear caches
        $this->cacheService->invalidateAll();
    }
}
