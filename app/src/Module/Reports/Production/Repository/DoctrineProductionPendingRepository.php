<?php

namespace App\Module\Reports\Production\Repository;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineProductionPendingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgreementLine::class);
    }

    public function getSummary(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ) {
        $query = $this->getQuery($start, $end)
            ->select('SUM(al.factor) as factors_summary')
            ->addSelect('COUNT(al.id) as count');
        return $query->getQuery()->getSingleResult();
    }

    public function getDetails(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array {
        $query = $this->getQuery($start, $end);
        $this->withPendingProductionTask($query)
            ->join('al.Product', 'p')
            ->join('al.Agreement', 'a')
            ->join('a.Customer', 'c')
            ->addSelect('al.id')
            ->addSelect('al.factor')
            ->addSelect('al.productionStartDate')
            ->addSelect('al.productionCompletionDate')
            ->addSelect('al.confirmedDate')
            ->addSelect('p.name as productName')
            ->addSelect('c.name as customerName')
            ->addSelect('a.orderNumber')
            ->addSelect('pr.departmentSlug');
        return $query->getQuery()->getArrayResult();
    }

    private function getQuery(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): QueryBuilder {
        $query = $this->createQueryBuilder('al');
        return $this->withinProductionStartDate($query, $start, $end)
            ->andWhere('al.deleted = 0')
            ->andWhere('al.productionCompletionDate IS NULL');
    }

    private function withinProductionStartDate(
        QueryBuilder $qb,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): QueryBuilder {
        if ($start) {
            $qb
                ->andWhere('al.productionStartDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    (new \DateTime())->setTimestamp($start->getTimestamp())->setTime(0, 0)
                );
        }
        if ($end) {
            $qb
                ->andWhere('al.productionStartDate <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    (new \DateTime())->setTimestamp($end->getTimestamp())->setTime(23, 59, 59)
                );
        }
        return $qb;
    }

    private function withPendingProductionTask(QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->leftJoin(
                'al.productions',
                'pr',
                'WITH',
                'pr.departmentSlug IN (:departments) AND pr.status IN (:qualifiedStatuses)'
            )
            ->addSelect('pr.completedAt')
            ->setParameter('departments', [
                TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_SLUG_CNC,
                TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING,
                TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_SLUG_INTOREX
            ])
            ->setParameter('qualifiedStatuses', [
                TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE
            ])
        ;
    }
}
