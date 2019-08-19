<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use App\Entity\Production;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Production|null find($id, $lockMode = null, $lockVersion = null)
 * @method Production|null findOneBy(array $criteria, array $orderBy = null)
 * @method Production[]    findAll()
 * @method Production[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Production::class);
    }

    /**
     * @param array|null $term
     * @return \Doctrine\ORM\Query
     * @throws \Exception
     */
    public function getFiltered(?array $term)
    {
        $qb = $this->createQueryBuilder('p');

        if ($term) {
            foreach ($term['search'] as $key => $value) {
                switch ($key) {
//                    case 'dateStart':
//                        $qb->andWhere("p.dateStart >= :{$key}");
//                        $qb->setParameter($key, (new \DateTime($value)));
//                        break;
//                    case 'dateEnd':
//                        $qb->andWhere("p.dateEnd <= :{$key}");
//                        $qb->setParameter($key, (new \DateTime($value . ' 23:59:59')));
//                        break;
                }
            }
        }

        return $qb->getQuery();
    }

    public function getNotCompletedAgreementLines(int $month, int $year)
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.createdAt <= :val')
            ->andWhere('p.departmentSlug = \'dpt05\'')
            ->andWhere('p.status != \'3\'')
            ->andWhere('l.archived = 0')
            ->andWhere('l.deleted = 0')
            ->setParameter('val', (new \DateTime($year . '-' . $month))->modify('last day of')->setTime(23, 59, 59))
            ->join('p.agreementLine', 'l')
            ->join('l.Product', 'pr')
            ->select('p, l, pr')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCompletedAgreementLines(int $month, int $year)
    {
        return $this
            ->createQueryBuilder('p')
            ->join('p.statusLogs', 'log')
            ->andWhere('p.status = \'3\'')
            ->andWhere('p.departmentSlug = \'dpt05\'')
            ->andWhere('log.currentStatus = \'3\'')
            ->andWhere('log.createdAt >= :dateFrom')
            ->andWhere('log.createdAt <= :dateTo')
            ->setParameter('dateFrom', (new \DateTime($year . '-' . $month))->modify('first day of')->setTime(0, 0, 0))
            ->setParameter('dateTo', (new \DateTime($year . '-' . $month))->modify('last day of')->setTime(23, 59, 59))
            ->join('p.agreementLine', 'l')
            ->andWhere('l.archived = 0')
            ->andWhere('l.deleted = 0')
            ->join('l.Product', 'pr')

            ->select('p, l, pr')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAllWithLogs(AgreementLine $agreementLine)
    {
        return $this
            ->createQueryBuilder('p')
            ->innerJoin('p.statusLogs', 'log')
            ->andWhere('p.agreementLine = :val')
            ->setParameter('val', $agreementLine)
            ->addSelect('log')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Production[] Returns an array of Production objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Production
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
