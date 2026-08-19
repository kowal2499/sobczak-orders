<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Command\ResetBonusPeriodAdjustmentsCommand;
use App\Module\BonusSettlement\Repository\BonusPeriodEntryRepository;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Zdejmuje wszystkie korekty w okresie, przywracając czysty wsad. Osobna akcja, bo przeliczenie
 * celowo korekt nie rusza.
 */
class ResetBonusPeriodAdjustmentsCommandHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly BonusPeriodEntryRepository $entryRepository,
        private readonly EntityManagerInterface $em,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(ResetBonusPeriodAdjustmentsCommand $command): void
    {
        $period = $this->periodRepository->find($command->periodId);
        if (null === $period) {
            throw new \InvalidArgumentException('Okres nie istnieje.');
        }

        $cleared = 0;
        foreach ($this->entryRepository->findByPeriod($period) as $entry) {
            if (!$entry->isAdjusted()) {
                continue;
            }
            $entry->clearAdjustment();
            ++$cleared;
        }

        $this->em->flush();

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.period.adjustments_reset',
            type: 'bonus.period.adjustments_reset',
            contextData: ['periodId' => (string) $period->getId()],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
                'clearedEntries' => $cleared,
            ],
        ));
    }
}
