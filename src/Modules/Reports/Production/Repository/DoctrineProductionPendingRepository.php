<?php

namespace App\Modules\Reports\Production\Repository;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class DoctrineProductionPendingRepository extends ServiceEntityRepository
{
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, AgreementLine::class);
        $this->security = $security;
    }

    public function getSummary(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    )
    {
        $query = $this->getQuery($start, $end)
            ->select('SUM(al.factor) as factors_summary')
            ->addSelect('COUNT(al.id) as count');
        return $query->getQuery()->getSingleResult();
    }

    public function getDetails(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
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
            ->groupBy('al.id');
        return $query->getQuery()->getArrayResult();
    }

    private function getQuery(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): QueryBuilder
    {
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
            ->join('al.productions', 'pr')
            ->addSelect('SUM(CASE WHEN (pr.departmentSlug = :dpt01 AND pr.status IN (:qualifiedStatuses)) THEN 1 ELSE 0 END) AS involved_dpt01')
            ->addSelect('SUM(CASE WHEN (pr.departmentSlug = :dpt02 AND pr.status IN (:qualifiedStatuses)) THEN 1 ELSE 0 END) AS involved_dpt02')
            ->addSelect('SUM(CASE WHEN (pr.departmentSlug = :dpt03 AND pr.status IN (:qualifiedStatuses)) THEN 1 ELSE 0 END) AS involved_dpt03')
            ->addSelect('SUM(CASE WHEN (pr.departmentSlug = :dpt04 AND pr.status IN (:qualifiedStatuses)) THEN 1 ELSE 0 END) AS involved_dpt04')
            ->addSelect('SUM(CASE WHEN (pr.departmentSlug = :dpt05 AND pr.status IN (:qualifiedStatuses)) THEN 1 ELSE 0 END) AS involved_dpt05')

            ->setParameter('qualifiedStatuses', [TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE])
            ->setParameter('dpt01', TaskTypes::TYPE_DEFAULT_SLUG_GLUING)
            ->setParameter('dpt02', TaskTypes::TYPE_DEFAULT_SLUG_CNC)
            ->setParameter('dpt03', TaskTypes::TYPE_DEFAULT_SLUG_GRINDING)
            ->setParameter('dpt04', TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING)
            ->setParameter('dpt05', TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING)
        ;
    }
}