<?php

namespace App\Module\Authorization\CommandHandler;

use App\Module\Authorization\Command\CreateUserGrantValue;
use App\Module\Authorization\Command\DeleteUserGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\Repository\UserRepository;
use InvalidArgumentException;

class DeleteUserGrantValueHandler
{
    public function __construct(
        private readonly AuthUserGrantValueRepository $authUserGrantValueRepository,
        private readonly AuthCacheService $cacheService,
    ) {
    }

    public function __invoke(DeleteUserGrantValue $command): void
    {
        $userGrantValue = $this->authUserGrantValueRepository->find($command->getUserGrantValueId());
        if (!$userGrantValue) {
            throw new InvalidArgumentException('User Grant Value not found');
        }
        $this->authUserGrantValueRepository->remove($userGrantValue);

        // clear caches
        $this->cacheService->invalidateAll();
    }
}
