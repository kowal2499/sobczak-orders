<?php

namespace App\Module\ActivityLog\Repository;

use App\Module\ActivityLog\Entity\LogField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogField>
 */
class LogFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogField::class);
    }
}
