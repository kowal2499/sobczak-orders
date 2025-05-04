<?php

namespace App\Module\Authorization\Repository;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthUserRole>
 *
 * @method AuthUserRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthUserRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthUserRole[]    findAll()
 * @method AuthUserRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthUserRoleRepository extends ServiceEntityRepository implements AuthUserRoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthUserRole::class);
    }

    public function add(AuthUserRole $userRole, bool $flush = true): void
    {
        $this->_em->persist($userRole);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param User $user
     * @return AuthUserRole[]
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('ur')
            ->andWhere('ur.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }
}