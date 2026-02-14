<?php

namespace App\Module\Authorization\CommandHandler;

use App\Module\Authorization\Command\CreateUserGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\Repository\UserRepository;
use InvalidArgumentException;

class CreateUserGrantValueHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthGrantRepository $authGrantRepository,
        private readonly AuthUserGrantValueRepository $authUserGrantValueRepository,
        private readonly AuthCacheService $cacheService,
    ) {
    }

    public function __invoke(CreateUserGrantValue $command): void
    {
        $user = $this->userRepository->find($command->getUserId());
        $grant = $this->authGrantRepository->find($command->getGrantId());
        if (!$user || !$grant) {
            throw new InvalidArgumentException('User or Grant not found');
        }
        $grantValue = $this->authUserGrantValueRepository->findOneByUserAndGrant(
            $user, $grant, $command->getGrantOptionSlug()
        );

        if (!$grantValue) {
            $grantValue = new AuthUserGrantValue(
                $user,
                $grant,
                $command->getGrantOptionSlug()
            );
        }
        $grantValue->setValue($command->getValue());

        $this->authUserGrantValueRepository->save($grantValue);

        // clear caches
        $this->cacheService->invalidateAll();
    }
}