<?php

namespace App\Repository;

use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    public function findLast(Production $production)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.production = :val')
            ->setParameter('val', $production)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return StatusLog[] Returns an array of StatusLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StatusLog
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
