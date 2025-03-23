<?php

namespace App\Repository;

use App\Entity\WorkingSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkingSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkingSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkingSchedule[]    findAll()
 * @method WorkingSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkingScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkingSchedule::class);
    }

    public function findByRange(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.date >= :start')
            ->andWhere('w.date <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}