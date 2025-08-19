<?php

namespace App\Module\Authorization\Repository;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;
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
class AuthUserGrantValueRepository extends ServiceEntityRepository implements AuthUserGrantValueRepositoryInterface
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

    public function findOneByUserAndGrant(User $user, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthUserGrantValue
    {
        $qb = $this->createQueryBuilder('augv')
            ->andWhere('augv.user = :valUser')
            ->andWhere('augv.grant = :valGrant')
            ->setParameter('valUser', $user)
            ->setParameter('valGrant', $authGrant);

        if ($grantOptionSlug === null) {
            $qb->andWhere('augv.grantOptionSlug IS NULL');
        } else {
            $qb->andWhere('augv.grantOptionSlug = :valSlug')
                ->setParameter('valSlug', $grantOptionSlug);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('augv')
            ->andWhere('augv.user = :valUser')
            ->setParameter('valUser', $user)
            ->getQuery()
            ->getResult();
    }

    public function add(AuthUserGrantValue $userGrantValue, bool $flush = true): void
    {
        $this->_em->persist($userGrantValue);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
