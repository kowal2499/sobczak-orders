<?php

namespace App\Module\Task\Repository;

use App\Entity\AgreementLine;
use App\Module\Task\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Finds a single entity by its primary key / identifier, excluding soft-deleted by default.
     *
     * @param mixed $id The identifier.
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return Task|null
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Task
    {
        $task = parent::find($id, $lockMode, $lockVersion);

        if ($task && $task->isDeleted()) {
            return null;
        }

        return $task;
    }

    /**
     * Finds entities by a set of criteria, excluding soft-deleted by default.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Task[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        if (!isset($criteria['isDeleted'])) {
            $criteria['isDeleted'] = false;
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria, excluding soft-deleted by default.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @return Task|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?Task
    {
        if (!isset($criteria['isDeleted'])) {
            $criteria['isDeleted'] = false;
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * Finds all entities in the repository, excluding soft-deleted.
     *
     * @return Task[]
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * Finds tasks for a specific agreement line
     *
     * @param AgreementLine $agreementLine
     * @param bool $includeDeleted
     * @return Task[]
     */
    public function findByAgreementLine(AgreementLine $agreementLine, bool $includeDeleted = false): array
    {
        $criteria = ['agreementLine' => $agreementLine];

        if (!$includeDeleted) {
            $criteria['isDeleted'] = false;
        }

        return parent::findBy($criteria, ['dateStart' => 'ASC']);
    }

    /**
     * Persist a task entity
     */
    public function save(Task $task, bool $flush = true): void
    {
        $this->getEntityManager()->persist($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a task entity (hard delete)
     */
    public function remove(Task $task, bool $flush = true): void
    {
        $this->getEntityManager()->remove($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
