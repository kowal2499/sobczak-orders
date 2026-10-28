<?php

namespace App\Module\BonusSettlement\QueryHandler;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Query\GetBonusPeriodsQuery;
use App\Module\BonusSettlement\Query\Helper\BonusPeriodMapper;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;

class GetBonusPeriodsQueryHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly BonusPeriodMapper $mapper,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(GetBonusPeriodsQuery $query): array
    {
        return array_map(
            fn (BonusPeriod $period) => $this->mapper->toListItem($period),
            $this->periodRepository->findAllNewestFirst()
        );
    }
}
