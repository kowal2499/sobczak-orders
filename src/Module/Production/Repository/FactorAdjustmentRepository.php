<?php

namespace App\Module\Production\Repository;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Production\Entity\FactorAdjustment;
use App\Module\Production\Repository\Interface\FactorAdjustmentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @deprecated
 * @extends ServiceEntityRepository<FactorAdjustment>
 *
 * @method FactorAdjustment|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactorAdjustment|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactorAdjustment[]    findAll()
 * @method FactorAdjustment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactorAdjustmentRepository extends ServiceEntityRepository implements FactorAdjustmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactorAdjustment::class);
    }

    public function save(FactorAdjustment $factorAdjust, bool $flush = true): void
    {
        $this->_em->persist($factorAdjust);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(FactorAdjustment $factorAdjust, bool $flush = true): void
    {
        $this->_em->remove($factorAdjust);
        if ($flush) {
            $this->_em->flush();
        }
    }


}