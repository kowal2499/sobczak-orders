<?php

namespace App\Repository;

use App\Entity\Agreement;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Agreement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Agreement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Agreement[]    findAll()
 * @method Agreement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Agreement::class);
    }

    public function getByCustomer(Customer $customer)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.Customer', 'c')
            ->andWhere('c = :val')
            ->setParameter('val', $customer)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getByCustomerPostalCode($postalCode)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.Customer', 'c')
            ->andWhere('c.postal_code = :val')
            ->setParameter('val', $postalCode)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Agreement[] Returns an array of Agreement objects
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
    public function findOneBySomeField($value): ?Agreement
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
