<?php

namespace App\Modules\Reports\Production\Repository;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class DoctrineProductionFinishedRepository extends ServiceEntityRepository
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
    ) {
        $query = $this->getQuery($start, $end)
            ->select('SUM(al.factor) as factors_summary')
            ->addSelect('COUNT(al.id) as count');
        return $query->getQuery()->getSingleResult();
    }

    public function getDetails(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ) {
        $query = $this->getQuery($start, $end);
        $this->withCompletedProductionTask($query)
            ->andWhere('al.deleted = 0')
            ->addSelect('al')
            ->groupBy('al.id')
        ;
        return $query->getQuery()->getArrayResult();
    }

    private function getQuery(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): QueryBuilder
    {
        $query = $this->createQueryBuilder('al');
        return $this->withinProductionFinishedDate($query, $start, $end)
            ->andWhere('al.deleted = 0')
            ->andWhere('al.productionStartDate IS NOT NULL');
    }

    private function withinProductionFinishedDate(
        QueryBuilder $qb,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): QueryBuilder {
        if ($start) {
            $qb
                ->andWhere('al.productionCompletionDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    (new \DateTime())->setTimestamp($start->getTimestamp())->setTime(0, 0)
                );
        }
        if ($end) {
            $qb
                ->andWhere('al.productionCompletionDate <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    (new \DateTime())->setTimestamp($end->getTimestamp())->setTime(23, 59, 59)
                );
        }
        return $qb;
    }

    private function withCompletedProductionTask(QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->join('al.productions', 'pr')
            ->andWhere('pr.departmentSlug IN (:departments)')
            ->andWhere('pr.status IN (:qualifiedStatuses)')
            ->setParameter('qualifiedStatuses', [TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED])
            ->setParameter('departments', [TaskTypes::getDefaultSlugs()])
        ;
    }
}