<?php

namespace App\Module\Production\Repository;

use App\Module\Production\Entity\Factor;
use App\Module\Production\Repository\Interface\FactorRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Factor>
 *
 * @method Factor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Factor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Factor[]    findAll()
 * @method Factor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactorRepository extends ServiceEntityRepository implements FactorRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Factor::class);
    }

    public function save(Factor $factor, bool $flush = true): void
    {
        $this->_em->persist($factor);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(Factor $factor, bool $flush = true): void
    {
        $this->_em->remove($factor);
        if ($flush) {
            $this->_em->flush();
        }
    }
}