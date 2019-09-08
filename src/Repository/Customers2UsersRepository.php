<?php

namespace App\Repository;

use App\Entity\Customers2Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Customers2Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customers2Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customers2Users[]    findAll()
 * @method Customers2Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Customers2UsersRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Customers2Users::class);
    }

    // /**
    //  * @return Customers2Users[] Returns an array of Customers2Users objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Customers2Users
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
