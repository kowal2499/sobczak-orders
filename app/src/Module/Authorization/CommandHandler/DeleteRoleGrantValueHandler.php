<?php

namespace App\Module\Authorization\CommandHandler;

use App\Module\Authorization\Command\DeleteRoleGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Service\AuthCacheService;
use InvalidArgumentException;

class DeleteRoleGrantValueHandler
{
    public function __construct(
        private readonly AuthRoleRepository $roleRepository,
        private readonly AuthGrantRepository $grantRepository,
        private readonly AuthRoleGrantValueRepository $roleGrantValueRepository,
        private readonly AuthCacheService $cacheService,
    ) {
    }

    public function __invoke(DeleteRoleGrantValue $command): void
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
            return;
        }

        $this->roleGrantValueRepository->remove($roleGrantValue);

        // clear caches
        $this->cacheService->invalidateAll();
    }
}
