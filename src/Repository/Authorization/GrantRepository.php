<?php

namespace App\Repository\Authorization;

use App\Entity\Grant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Grant>
 *
 * @method Grant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Grant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Grant[]    findAll()
 * @method Grant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GrantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grant::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Grant $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function save(Grant $grant, bool $flush = true): void
    {
        $this->_em->persist($grant);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneBySlug(string $slug): ?Grant
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.slug = :val')
            ->setParameter('val', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
