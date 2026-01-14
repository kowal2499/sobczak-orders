<?php

namespace App\Module\AgreementLine\Repository;

use App\Module\AgreementLine\Entity\AgreementLineRM;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgreementLineRM>
 *
 * @method AgreementLineRM|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgreementLineRM|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgreementLineRM[]    findAll()
 * @method AgreementLineRM[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementLineRMRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgreementLineRM::class);
    }

    public function remove(AgreementLineRM $agreementLineRM, bool $flush = true): void
    {
        $this->_em->remove($agreementLineRM);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function add(AgreementLineRM $agreementLineRM, bool $flush = true): void
    {
        $this->_em->persist($agreementLineRM);
        if ($flush) {
            $this->_em->flush();
        }
    }
}