<?php

namespace App\Module\BonusSettlement\Repository;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusPeriod>
 */
class BonusPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusPeriod::class);
    }

    public function findOneByYearAndMonth(int $year, int $month): ?BonusPeriod
    {
        return $this->findOneBy(['year' => $year, 'month' => $month]);
    }

    /**
     * @return BonusPeriod[] od najnowszego okresu
     */
    public function findAllNewestFirst(): array
    {
        return $this->findBy([], ['year' => 'DESC', 'month' => 'DESC']);
    }

    public function save(BonusPeriod $period, bool $flush = true): void
    {
        $this->getEntityManager()->persist($period);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
