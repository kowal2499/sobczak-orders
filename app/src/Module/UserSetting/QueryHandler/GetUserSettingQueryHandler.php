<?php

namespace App\Module\UserSetting\QueryHandler;

use App\Module\UserSetting\Query\GetUserSettingQuery;
use App\Module\UserSetting\Repository\UserSettingRepository;

class GetUserSettingQueryHandler
{
    public function __construct(
        private readonly UserSettingRepository $userSettingRepository,
    ) {
    }

    public function __invoke(GetUserSettingQuery $query): ?array
    {
        $userSetting = $this->userSettingRepository->findOneByUserIdAndContext($query->userId, $query->context);

        return $userSetting?->getData();
    }
}
