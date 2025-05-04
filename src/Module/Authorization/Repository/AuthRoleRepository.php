<?php

namespace App\Module\Authorization\Repository;

use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Repository\Interface\AuthRoleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthRole>
 *
 * @method AuthRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthRole[]    findAll()
 * @method AuthRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthRoleRepository extends ServiceEntityRepository implements AuthRoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthRole::class);
    }

    public function add(AuthRole $role, bool $flush = true): void
    {
        $this->_em->persist($role);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneByName(string $name): ?AuthRole
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :val')
            ->setParameter('val', $name)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

//    /**
//     * @return Role[] Returns an array of Role objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
}
