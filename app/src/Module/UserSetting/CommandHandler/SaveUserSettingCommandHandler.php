<?php

namespace App\Module\UserSetting\CommandHandler;

use App\Module\UserSetting\Command\SaveUserSettingCommand;
use App\Module\UserSetting\Entity\UserSetting;
use App\Module\UserSetting\Repository\UserSettingRepository;
use App\Repository\UserRepository;

class SaveUserSettingCommandHandler
{
    public function __construct(
        private readonly UserSettingRepository $userSettingRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(SaveUserSettingCommand $command): void
    {
        $user = $this->userRepository->find($command->userId);

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $userSetting = $this->userSettingRepository->findOneByUserAndContext($user, $command->context);

        if ($userSetting === null) {
            $userSetting = new UserSetting($user, $command->context, $command->data);
        } else {
            $userSetting->setData($command->data);
        }

        $this->userSettingRepository->save($userSetting);
    }
}
