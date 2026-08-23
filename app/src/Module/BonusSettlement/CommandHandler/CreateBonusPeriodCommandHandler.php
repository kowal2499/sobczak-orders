<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Command\CreateBonusPeriodCommand;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\Module\Reports\Production\Metric\DepartmentsBonusOnTimeMetricStrategy;
use App\System\CommandBus;

class CreateBonusPeriodCommandHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(CreateBonusPeriodCommand $command): void
    {
        $existing = $this->periodRepository->findOneByYearAndMonth($command->year, $command->month);
        if (null !== $existing) {
            throw new \InvalidArgumentException(
                sprintf('Okres %04d-%02d już istnieje.', $command->year, $command->month)
            );
        }

        // Świeży okres startuje z domyślnymi widełkami miernika; przeliczenie może je nadpisać.
        $period = new BonusPeriod(
            $command->year,
            $command->month,
            DepartmentsBonusOnTimeMetricStrategy::DEFAULT_TOLERANCE_DAYS
        );

        $this->periodRepository->save($period);

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.period.created',
            type: 'bonus.period.created',
            contextData: ['periodId' => (string) $period->getId()],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
                'toleranceDays' => $period->getToleranceDays(),
            ],
        ));
    }
}
