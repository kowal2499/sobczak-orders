<?php

namespace App\Module\Production\Repository;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Production\Entity\FactorAdjust;
use App\Module\Production\Repository\Interface\FactorAdjustRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactorAdjust>
 *
 * @method FactorAdjust|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactorAdjust|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactorAdjust[]    findAll()
 * @method FactorAdjust[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactorAdjustRepository extends ServiceEntityRepository implements FactorAdjustRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactorAdjust::class);
    }

    public function add(FactorAdjust $factorAdjust, bool $flush = true): void
    {
        $this->_em->persist($factorAdjust);
        if ($flush) {
            $this->_em->flush();
        }
    }
}