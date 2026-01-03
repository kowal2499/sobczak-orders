<?php

namespace App\Module\Reports\Production\Repository;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class DoctrineProductionTasksRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Production::class);
    }

    public function getProductions(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        $query = $this->createQueryBuilder('p');
        $this->withProductionDepartments($query);
        $this->withinTaskCompletionDate($query, $start, $end);
        return $this->baseQuery($query);
    }

    public function getCapacityInTime(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array
    {
        $query = $this->createQueryBuilder('p');
        $this->withProductionDepartments($query);
        $this->withinTaskDuration($query, $start, $end);
         return $this->baseQuery($query);
    }

    private function withinTaskCompletionDate(
        QueryBuilder $qb,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): void {
        $qb->andWhere('p.isCompleted = 1');
        $qb->andWhere('p.completedAt IS NOT NULL');
        if ($start) {
            $qb
                ->andWhere('p.completedAt >= :dateStart')
                ->setParameter('dateStart', (clone $start)->setTime(0, 0));
        }
        if ($end) {
            $qb
                ->andWhere('p.completedAt <= :dateEnd')
                ->setParameter('dateEnd', (clone $end)->setTime(23, 59, 59));
        }
    }

    public function withinTaskDuration(
        QueryBuilder $qb,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): void
    {
            $qb->andWhere(
                $qb->expr()->orX(
                    // cały czas w zakresie
                    $qb->expr()->andX('p.dateStart >= :dateStart', 'p.dateEnd <= :dateEnd'),
                    // start przed zakresem, koniec w zakresie
                    $qb->expr()->andX('p.dateStart < :dateStart', 'p.dateEnd >= :dateStart', 'p.dateEnd <= :dateEnd'),
                    // start w zakresie, koniec poza zakresem
                    $qb->expr()->andX('p.dateStart >= :dateStart', 'p.dateStart <= :dateEnd', 'p.dateEnd > :dateEnd')
                )
            )

            ->setParameter('dateStart', (clone $start)->setTime(0, 0))
            ->setParameter('dateEnd', (clone $end)->setTime(23, 59, 59)
        );
    }

    public function withProductionDepartments(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('p.departmentSlug IN (:defaultDepartment)')
            ->setParameter('defaultDepartment', TaskTypes::getDefaultSlugs())
        ;
    }

    protected function baseQuery(QueryBuilder $qb): array
    {
        return $qb
            ->join('p.agreementLine', 'al')
            ->join('al.Product', 'pr')
            ->join('al.Agreement', 'a')
            ->join('a.Customer', 'c')
            ->addSelect('p.departmentSlug')
            ->addSelect('p.completedAt')
            ->addSelect('p.dateStart')
            ->addSelect('p.dateEnd')
            ->addSelect('p.status')
            ->addSelect('al.id')
            ->addSelect('al.factor')
            ->addSelect('al.productionStartDate')
            ->addSelect('al.productionCompletionDate')
            ->addSelect('al.confirmedDate')
            ->addSelect('pr.name as productName')
            ->addSelect('c.name as customerName')
            ->addSelect('a.orderNumber')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}