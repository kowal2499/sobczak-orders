<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Command\ReopenBonusPeriodCommand;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Jedyna droga do zmiany rozliczonego miesiąca, więc zawsze zostawia ślad w dzienniku -
 * razem z tym, kto i kiedy okres zamykał, bo ta informacja przy otwarciu przepada.
 */
class ReopenBonusPeriodCommandHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly EntityManagerInterface $em,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(ReopenBonusPeriodCommand $command): void
    {
        $period = $this->periodRepository->find($command->periodId);
        if (null === $period) {
            throw new \InvalidArgumentException('Okres nie istnieje.');
        }
        if (!$period->isClosed()) {
            throw new \InvalidArgumentException('Okres nie jest zamknięty.');
        }

        $closedAt = $period->getClosedAt();
        $closedBy = $period->getClosedBy();

        $period->reopen();
        $this->em->flush();

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.period.reopened',
            type: 'bonus.period.reopened',
            contextData: ['periodId' => (string) $period->getId()],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
                // null trafiłby do komunikatu jako słowo "null" - dziennik ma się czytać, nie debugować
                'closedAt' => $closedAt?->format('Y-m-d H:i:s') ?? '-',
                'closedByLabel' => $closedBy?->getUserFullName() ?? '-',
            ],
        ));
    }
}
