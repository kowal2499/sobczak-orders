<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function getWithSearch(?array $term)
    {
        $qb = $this->createQueryBuilder('c');

        foreach ((array)$term as $key => $value) {
            switch ($key) {
                case 'q':
                    $qb->andWhere('c.name LIKE :term OR c.first_name LIKE :term OR c.last_name LIKE :term OR c.street LIKE :term OR c.city LIKE :term OR c.country LIKE :term OR c.phone LIKE :term OR c.email LIKE :term')
                        ->setParameter('term', '%' . $value . '%');
                    break;
                case 'ownedBy':
                    $customers = $value->getCustomers();
                    if (!empty($customers)) {
                        $qb->andWhere("c.id IN (:{$key})");
                        $qb->setParameter($key, $customers);
                    }
                    break;

            }
        }


        return $qb
            ->orderBy('c.name, c.first_name, c.last_name', 'DESC')
            ->getQuery()
        ;
    }


    // /**
    //  * @return Customer[] Returns an array of Customer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
