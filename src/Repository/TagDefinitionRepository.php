<?php
/** @author: Roman Kowalski */

namespace App\Repository;

use App\Entity\TagDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class TagDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagDefinition::class);
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function notDeleted(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('t')
            ->where('t.isDeleted = false')
            ->getQuery();
    }
}