<?php

namespace App\Module\BonusSettlement\QueryHandler;

use App\Module\BonusSettlement\Query\GetBonusPeriodQuery;
use App\Module\BonusSettlement\Query\Helper\BonusPeriodMapper;
use App\Module\BonusSettlement\Repository\BonusPeriodEntryRepository;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;

class GetBonusPeriodQueryHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly BonusPeriodEntryRepository $entryRepository,
        private readonly BonusPeriodMapper $mapper,
    ) {
    }

    /**
     * @return array<string, mixed>|null null, gdy okresu nie ma - kontroler zamienia to na 404
     */
    public function __invoke(GetBonusPeriodQuery $query): ?array
    {
        $period = $this->periodRepository->find($query->periodId);
        if (null === $period) {
            return null;
        }

        return $this->mapper->toDetail($period, $this->entryRepository->findByPeriod($period));
    }
}
