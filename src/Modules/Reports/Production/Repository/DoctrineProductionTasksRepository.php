<?php

namespace App\Modules\Reports\Production\Repository;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class DoctrineProductionTasksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Production::class);
        $this->security = $security;
    }

    public function getProductions(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        $query = $this->createQueryBuilder('p');
        return
            $this->withinTaskCompletionDate($query, $start, $end)
            ->andWhere('p.departmentSlug IN (:defaultDepartment)')
            ->setParameter('defaultDepartment', TaskTypes::getDefaultSlugs())
            ->join('p.agreementLine', 'al')
            ->join('al.Product', 'pr')
            ->join('al.Agreement', 'a')
            ->join('a.Customer', 'c')
            ->addSelect('p.departmentSlug')
            ->addSelect('p.completedAt')
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

    private function withinTaskCompletionDate(
        QueryBuilder $qb,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): QueryBuilder {
        $qb->andWhere('p.isCompleted = 1');
        $qb->andWhere('p.completedAt IS NOT NULL');
        if ($start) {
            $qb
                ->andWhere('p.completedAt >= :dateStart')
                ->setParameter(
                    'dateStart',
                    (new \DateTime())->setTimestamp($start->getTimestamp())->setTime(0, 0)
                );
        }
        if ($end) {
            $qb
                ->andWhere('p.completedAt <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    (new \DateTime())->setTimestamp($end->getTimestamp())->setTime(23, 59, 59)
                );
        }
        return $qb;
    }
}