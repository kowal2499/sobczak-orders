<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Command\CloseBonusPeriodCommand;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\Repository\UserRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;

class CloseBonusPeriodCommandHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(CloseBonusPeriodCommand $command): void
    {
        $period = $this->periodRepository->find($command->periodId);
        if (null === $period) {
            throw new \InvalidArgumentException('Okres nie istnieje.');
        }
        if ($period->isClosed()) {
            throw new \InvalidArgumentException('Okres jest już zamknięty.');
        }

        $user = $this->userRepository->find($command->closedByUserId);
        if (null === $user) {
            throw new \InvalidArgumentException('Użytkownik zamykający nie istnieje.');
        }

        $period->close($user);
        $this->em->flush();

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.period.closed',
            type: 'bonus.period.closed',
            contextData: ['periodId' => (string) $period->getId()],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
            ],
        ));
    }
}
