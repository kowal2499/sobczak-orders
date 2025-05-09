<?php

namespace App\Repository;

use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatusLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatusLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatusLog[]    findAll()
 * @method StatusLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusLog::class);
    }

    public function findLast(Production $production, int $status = null)
    {
        $qb = $this->createQueryBuilder('s');
        if ($status) {
            $qb
                ->andWhere('s.currentStatus = :valStatus')
                ->setParameter('valStatus', $status)
            ;
        }
        return $qb
            ->andWhere('s.production = :val')
            ->setParameter('val', $production)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
