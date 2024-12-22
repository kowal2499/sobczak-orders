<?php

namespace App\Repository;

use App\Entity\Agreement;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Agreement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Agreement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Agreement[]    findAll()
 * @method Agreement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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
}
