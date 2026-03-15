<?php

namespace App\Module\Tag\Repository;

use App\Module\Tag\Entity\TagAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagAssignment::class);
    }

    public function findAllInModule(int $contextId, string $module): array
    {
        return $this->createQueryBuilder('ta')
            ->innerJoin('ta.tagDefinition', 'td')
            ->andWhere('td.module = :module')
            ->andWhere('ta.contextId = :context')
            ->setParameters([
                'module' => $module,
                'context' => $contextId,
            ])
            ->getQuery()
            ->getResult();
    }
}
