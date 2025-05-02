<?php

namespace App\Module\Authorization\Repository;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthRoleGrantValue>
 *
 * @method AuthUserGrantValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthUserGrantValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthUserGrantValue[]    findAll()
 * @method AuthUserGrantValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthUserGrantValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthUserGrantValue::class);
    }

    public function save(AuthUserGrantValue $authUserGrantValue, bool $flush = true): void
    {
        $this->_em->persist($authUserGrantValue);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneByUserAndGrant(User $user, AuthGrant $authGrant): ?AuthUserGrantValue
    {
        return $this->createQueryBuilder('augv')
            ->andWhere('augv.user = :valUser')
            ->andWhere('augv.grant = :valGrant')
            ->setParameter('valUser', $user)
            ->setParameter('valGrant', $authGrant)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
//
//    /**
//     * @param AuthRole $authRole
//     * @return AuthRoleGrantValue[]
//     */
//    public function findAllByRole(AuthRole $authRole): array
//    {
//        return $this->createQueryBuilder('augv')
//            ->andWhere('augv.role = :valRole')
//            ->setParameter('valRole', $authRole)
//            ->getQuery()
//            ->getResult();
//    }

}
