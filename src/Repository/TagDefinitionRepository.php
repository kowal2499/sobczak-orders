<?php
/** @author: Roman Kowalski */

namespace App\Repository;

use App\Entity\TagDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class TagDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagDefinition::class);
    }

    /**
     * @return Query
     */
    public function notDeleted(): Query
    {
        return $this->createQueryBuilder('t')
            ->where('t.isDeleted = false')
            ->getQuery();
    }

    /**
     * @param $module
     * @return Query
     */
    public function findByModule($module): Query
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDeleted = false')
            ->andWhere('t.module = :m')
            ->setParameter('m', $module)
            ->getQuery();
    }

    public function findAllById(array $ids)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDeleted = false')
            ->andWhere('t.id IN (:idarray)')
            ->setParameter('idarray', $ids)
            ->getQuery()
            ->getResult();
    }
}