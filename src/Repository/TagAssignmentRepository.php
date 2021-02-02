<?php

namespace App\Repository;

use App\Entity\TagAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TagAssignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method TagAssignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method TagAssignment[]    findAll()
 * @method TagAssignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagAssignment::class);
    }

    public function findAllInModule($contextId, $module)
    {
        return $this->createQueryBuilder('ta')
            ->innerJoin('ta.tagDefinition', 'td')
            ->andWhere('td.module = :module')
            ->andWhere('ta.contextId = :context')
            ->setParameters([
                'module' => $module,
                'context' => $contextId
            ])
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return TagAssignment[] Returns an array of TagAssignment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TagAssignment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
