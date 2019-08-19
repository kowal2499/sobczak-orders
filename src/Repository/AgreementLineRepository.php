<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AgreementLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgreementLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgreementLine[]    findAll()
 * @method AgreementLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementLineRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AgreementLine::class);
    }

    public function getFiltered(?array $term)
    {
        $qb = $this->createQueryBuilder('l')
            ->innerJoin('l.Agreement', 'a')
            ->innerJoin('a.Customer', 'c')
            ->innerJoin('l.Product', 'p')
            ->leftJoin('l.productions', 'pr')
            ->leftJoin('pr.statusLogs', 's')
            ->addSelect('a')
            ->addSelect('c')
            ->addSelect('p')
            ->addSelect('pr')
            ->addSelect('s')
        ;

        if (is_array($term['search'])) {

            foreach ($term['search'] as $key => $value) {
                switch ($key) {
                    case 'dateStart':
                        $qb->andWhere("a.createDate >= :{$key}");
                        $qb->setParameter($key, (new \DateTime($value)));
                        break;
                    case 'dateEnd':
                        $qb->andWhere("a.createDate <= :{$key}");
                        $qb->setParameter($key, (new \DateTime($value . ' 23:59:59')));
                        break;
                    case 'archived':
                        $qb->andWhere("l.archived = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                    case 'deleted':
                        $qb->andWhere("l.deleted = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                    case 'agreementLineId':
                        $qb->andWhere("l.id = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                }
            }
        
        }

        return $qb->getQuery();
    }

    // /**
    //  * @return AgreementLine[] Returns an array of AgreementLine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AgreementLine
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
