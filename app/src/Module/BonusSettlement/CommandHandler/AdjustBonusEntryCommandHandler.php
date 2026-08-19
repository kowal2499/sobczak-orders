<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Command\AdjustBonusEntryCommand;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\Module\BonusSettlement\Repository\BonusPeriodEntryRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;

class AdjustBonusEntryCommandHandler
{
    public function __construct(
        private readonly BonusPeriodEntryRepository $entryRepository,
        private readonly EntityManagerInterface $em,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(AdjustBonusEntryCommand $command): void
    {
        $entry = $this->entryRepository->find($command->entryId);
        if (null === $entry) {
            throw new \InvalidArgumentException('Wiersz rozliczenia nie istnieje.');
        }
        if ($entry->getPeriod()->isClosed()) {
            throw new \InvalidArgumentException('Okres jest zamknięty - najpierw otwórz go ponownie.');
        }

        $before = $entry->getEffectiveFactors();

        if (null === $command->factorsAdjusted) {
            $entry->clearAdjustment();
        } else {
            $entry->adjust($command->factorsAdjusted, $command->note);
        }

        $this->em->flush();

        $this->log($entry, $before);
    }

    /**
     * To pieniądze - w dzienniku ląduje wartość przed i po, żeby dało się odtworzyć decyzję
     * bez zaglądania w historię tabeli.
     */
    private function log(BonusPeriodEntry $entry, float $before): void
    {
        $period = $entry->getPeriod();

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.entry.adjusted',
            type: 'bonus.entry.adjusted',
            contextData: [
                'periodId' => (string) $period->getId(),
                'entryId' => (string) $entry->getId(),
                'userId' => (string) $entry->getUser()->getId(),
            ],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
                'userLabel' => $entry->getUserLabel(),
                'departmentLabel' => $entry->getDepartmentLabel(),
                'valueBefore' => $before,
                'valueAfter' => $entry->getEffectiveFactors(),
                'note' => $entry->getNote(),
            ],
        ));
    }
}
