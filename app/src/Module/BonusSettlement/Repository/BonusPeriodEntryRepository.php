<?php

namespace App\Module\BonusSettlement\Repository;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusPeriodEntry>
 */
class BonusPeriodEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusPeriodEntry::class);
    }

    /**
     * @return BonusPeriodEntry[]
     */
    public function findByPeriod(BonusPeriod $period): array
    {
        return $this->findBy(['period' => $period], ['userLabel' => 'ASC', 'departmentSlug' => 'ASC']);
    }

    public function save(BonusPeriodEntry $entry, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entry);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
