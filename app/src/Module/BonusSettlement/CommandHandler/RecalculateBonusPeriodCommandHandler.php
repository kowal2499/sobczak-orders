<?php

namespace App\Module\BonusSettlement\CommandHandler;

use App\Module\BonusSettlement\Command\RecalculateBonusPeriodCommand;
use App\Module\BonusSettlement\DTO\DepartmentMembershipDTO;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\Module\BonusSettlement\Repository\BonusPeriodEntryRepository;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\Module\BonusSettlement\Service\DepartmentFactorsCalculator;
use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\BonusSettlement\Service\DepartmentMembershipProvider;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Odświeża wsad okresu: liczy sumy działów za miesiąc okresu i rozdaje je pracownikom
 * mającym grant danego działu.
 *
 * Korekty przeżywają przeliczenie - dopasowanie po kluczu użytkownik + dział. Wiersz, którego
 * użytkownik stracił grant działowy, znika razem z korektą.
 */
class RecalculateBonusPeriodCommandHandler
{
    public function __construct(
        private readonly BonusPeriodRepository $periodRepository,
        private readonly BonusPeriodEntryRepository $entryRepository,
        private readonly DepartmentFactorsCalculator $factorsCalculator,
        private readonly DepartmentMembershipProvider $membershipProvider,
        private readonly EntityManagerInterface $em,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(RecalculateBonusPeriodCommand $command): void
    {
        $period = $this->periodRepository->find($command->periodId);
        if (null === $period) {
            throw new \InvalidArgumentException('Okres nie istnieje.');
        }

        $tolerance = $command->toleranceDays ?? $period->getToleranceDays();
        $sums = $this->factorsCalculator->forRange(
            $period->getRangeStart(),
            $period->getRangeEnd(),
            $tolerance
        );

        $obsolete = $this->existingByKey($period);

        foreach ($this->membershipProvider->all() as $membership) {
            $key = $this->keyOf($membership->user->getId(), $membership->departmentSlug);
            $factors = $sums[$membership->departmentSlug] ?? 0.0;

            if (isset($obsolete[$key])) {
                $this->refresh($obsolete[$key], $membership, $factors);
                unset($obsolete[$key]);
                continue;
            }

            $this->em->persist($this->create($period, $membership, $factors));
        }

        // co zostało w $obsolete, straciło podstawę - użytkownik nie ma już grantu działowego
        foreach ($obsolete as $entry) {
            $this->em->remove($entry);
        }

        $period->setToleranceDays($tolerance);
        $period->setCalculatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.bonus.period.recalculated',
            type: 'bonus.period.recalculated',
            contextData: ['periodId' => (string) $period->getId()],
            contentParams: [
                'period' => sprintf('%04d-%02d', $period->getYear(), $period->getMonth()),
                'toleranceDays' => $tolerance,
                'removedEntries' => count($obsolete),
            ],
        ));
    }

    /**
     * @return array<string, BonusPeriodEntry>
     */
    private function existingByKey(BonusPeriod $period): array
    {
        $existing = [];
        foreach ($this->entryRepository->findByPeriod($period) as $entry) {
            $existing[$this->keyOf($entry->getUser()->getId(), $entry->getDepartmentSlug())] = $entry;
        }

        return $existing;
    }

    private function keyOf(?int $userId, string $departmentSlug): string
    {
        return $userId . '|' . $departmentSlug;
    }

    private function create(
        BonusPeriod $period,
        DepartmentMembershipDTO $membership,
        float $factors
    ): BonusPeriodEntry {
        return new BonusPeriodEntry(
            $period,
            $membership->user,
            $membership->userLabel,
            $membership->departmentSlug,
            $membership->departmentLabel,
            $factors
        );
    }

    /**
     * Odświeżamy wyłącznie wsad i etykiety. Korekta, jej notatka, czas i wsad, przy którym
     * zapadła, zostają nietknięte - inaczej zniknęłoby ostrzeżenie o korekcie do nieaktualnego
     * wyliczenia.
     */
    private function refresh(BonusPeriodEntry $entry, DepartmentMembershipDTO $membership, float $factors): void
    {
        $entry->setFactorsCalculated($factors);
        $entry->setUserLabel($membership->userLabel);
        $entry->setDepartmentLabel($membership->departmentLabel);
    }
}
