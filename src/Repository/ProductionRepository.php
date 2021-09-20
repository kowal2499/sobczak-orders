<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use App\Entity\Production;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;


/**
 * @method Production|null find($id, $lockMode = null, $lockVersion = null)
 * @method Production|null findOneBy(array $criteria, array $orderBy = null)
 * @method Production[]    findAll()
 * @method Production[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductionRepository extends ServiceEntityRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Production::class);
        $this->security = $security;
    }

    /**
     * @param array|null $term
     * @return \Doctrine\ORM\Query
     * @throws \Exception
     */
    public function getFiltered(?array $term)
    {
        $qb = $this->createQueryBuilder('p');

        if ($term) {
            foreach ($term['search'] as $key => $value) {
                switch ($key) {
//                    case 'dateStart':
//                        $qb->andWhere("p.dateStart >= :{$key}");
//                        $qb->setParameter($key, (new \DateTime($value)));
//                        break;
//                    case 'dateEnd':
//                        $qb->andWhere("p.dateEnd <= :{$key}");
//                        $qb->setParameter($key, (new \DateTime($value . ' 23:59:59')));
//                        break;
                }
            }
        }

        return $qb->getQuery();
    }

    public function withConnectedCustomers(QueryBuilder $qb) {

        if ($this->security->isGranted('ROLE_CUSTOMER')) {
            $customers = $this->security->getUser()->getCustomers();
            if (!empty($customers)) {
                $qb
                    ->andWhere('c.id IN (:ownedCustomers)')
                    ->setParameter('ownedCustomers', $customers);
            }

        }

        return $qb;
    }

    public function getNotCompletedAgreementLines(int $month, int $year)
    {
        $query =  $this
            ->createQueryBuilder('p')
            ->andWhere('p.createdAt <= :val')
            ->andWhere('p.departmentSlug = \'dpt05\'')
            ->andWhere('p.status != \'3\'')
            ->andWhere('l.status IN (:statusKey)')
            ->andWhere('l.deleted = 0')
            ->setParameter('val', (new \DateTime($year . '-' . $month))->modify('last day of')->setTime(23, 59, 59))
            ->setParameter('statusKey', [AgreementLine::STATUS_WAITING, AgreementLine::STATUS_MANUFACTURING])
            ->join('p.agreementLine', 'l')
            ->join('l.Product', 'pr')
            ->join('l.Agreement', 'a')
            ->join('a.Customer', 'c')
            ->select('p, l, pr')
        ;

        return $query;
    }

    public function getCompletedAgreementLines(int $month, int $year)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->join('p.statusLogs', 'log')
            ->andWhere('p.status = \'3\'')
            ->andWhere('p.departmentSlug = \'dpt05\'')
            ->andWhere('log.currentStatus = \'3\'')
            ->andWhere('log.createdAt >= :dateFrom')
            ->andWhere('log.createdAt <= :dateTo')
            ->setParameter('dateFrom', (new \DateTime($year . '-' . $month))->modify('first day of')->setTime(0, 0, 0))
            ->setParameter('dateTo', (new \DateTime($year . '-' . $month))->modify('last day of')->setTime(23, 59, 59))
            ->join('p.agreementLine', 'l')
            ->andWhere('l.deleted = 0')
            ->join('l.Product', 'pr')
            ->join('l.Agreement', 'a')
            ->join('a.Customer', 'c')
            ->andWhere('l.status NOT IN (:statusNotInKey)')
            ->setParameter('statusNotInKey', [AgreementLine::STATUS_DELETED])

            ->select('p, l, pr')
        ;
        $query = $this->withConnectedCustomers($query);

        return $query->getQuery()->getResult();
    }

    public function getAllWithLogs(AgreementLine $agreementLine)
    {
        return $this
            ->createQueryBuilder('p')
            ->innerJoin('p.statusLogs', 'log')
            ->andWhere('p.agreementLine = :val')
            ->setParameter('val', $agreementLine)
            ->addSelect('log')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAgreementLinesFinishedRefactored(\DateTimeInterface $start, \DateTimeInterface $end)
    {

    }

    public function getAgreementLinesPendingRefactored(\DateTimeInterface $start, \DateTimeInterface $end)
    {

    }
}
