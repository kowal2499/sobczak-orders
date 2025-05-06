<?php

namespace App\Module\Authorization\Repository;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthGrant>
 *
 * @method AuthGrant|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthGrant|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthGrant[]    findAll()
 * @method AuthGrant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthGrantRepository extends ServiceEntityRepository implements AuthGrantRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthGrant::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(AuthGrant $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function save(AuthGrant $grant, bool $flush = true): void
    {
        $this->_em->persist($grant);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneBySlug(string $slug): ?AuthGrant
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.slug = :val')
            ->setParameter('val', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
