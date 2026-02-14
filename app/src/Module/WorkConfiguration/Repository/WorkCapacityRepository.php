<?php

namespace App\Module\WorkConfiguration\Repository;

use App\Module\WorkConfiguration\Entity\WorkCapacity;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkCapacity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkCapacity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkCapacity[]    findAll()
 * @method WorkCapacity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkCapacityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkCapacity::class);
    }

    public function findOneByDate(\DateTimeImmutable $date): ?WorkCapacity
    {
        $dateWithoutTime = (clone $date)->setTime(0, 0, 0);

        return $this->createQueryBuilder('w')
            ->where('w.dateFrom <= :date')
            ->setParameter('date', $dateWithoutTime)
            ->orderBy('w.dateFrom', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRange(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('w');

        if ($start) {
            $qb->andWhere('w.dateFrom >= :start')
               ->setParameter('start', $start);
        }
        if ($end) {
            $qb->andWhere('w.dateFrom <= :end')
               ->setParameter('end', $end);
        }

        return $qb
            ->orderBy('w.dateFrom', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(WorkCapacity $workCapacity, bool $flush = true): void
    {
        $this->_em->persist($workCapacity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(WorkCapacity $workCapacity, bool $flush = true): void
    {
        $this->_em->remove($workCapacity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Upsert - if entry for given dateFrom exists, update it, otherwise create new one
     */
    public function upsert(\DateTimeInterface $dateFrom, float $capacity, bool $flush = true): WorkCapacity
    {
        $existing = $this->findOneBy(['dateFrom' => $dateFrom]);

        if ($existing) {
            $existing->setCapacity($capacity);
            $workCapacity = $existing;
        } else {
            $workCapacity = new WorkCapacity($dateFrom, $capacity);
        }

        $this->save($workCapacity, $flush);

        return $workCapacity;
    }
}
