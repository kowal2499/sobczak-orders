<?php

namespace App\Module\Authorization\Repository;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthRoleGrantValue>
 *
 * @method AuthRoleGrantValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthRoleGrantValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthRoleGrantValue[]    findAll()
 * @method AuthRoleGrantValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthRoleGrantValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthRoleGrantValue::class);
    }

    public function save(AuthRoleGrantValue $authRoleGrantValue, bool $flush = true): void
    {
        $this->_em->persist($authRoleGrantValue);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneByRoleAndGrant(AuthRole $authRole, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthRoleGrantValue
    {
        $qb = $this->createQueryBuilder('argv')
            ->andWhere('argv.role = :valRole')
            ->andWhere('argv.grant = :valGrant')
            ->setParameter('valRole', $authRole)
            ->setParameter('valGrant', $authGrant);

        if ($grantOptionSlug === null) {
            $qb->andWhere('argv.grantOptionSlug IS NULL');
        } else {
            $qb->andWhere('argv.grantOptionSlug = :valSlug')
                ->setParameter('valSlug', $grantOptionSlug);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AuthRole $authRole
     * @return AuthRoleGrantValue[]
     */
    public function findAllByRole(AuthRole $authRole): array
    {
        return $this->createQueryBuilder('argv')
            ->andWhere('argv.role = :valRole')
            ->setParameter('valRole', $authRole)
            ->getQuery()
            ->getResult();
    }

}
