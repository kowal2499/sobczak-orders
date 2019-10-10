<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @method AgreementLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgreementLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgreementLine[]    findAll()
 * @method AgreementLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementLineRepository extends ServiceEntityRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(RegistryInterface $registry, Security $security)
    {
        parent::__construct($registry, AgreementLine::class);
        $this->security = $security;
    }

    public function getFiltered(?array $term)
    {
        $qb = $this->createQueryBuilder('l')
            ->innerJoin('l.Agreement', 'a')
            ->innerJoin('a.Customer', 'c')
            ->innerJoin('l.Product', 'p')
            ->leftJoin('l.productions', 'pr')
            ->leftJoin('pr.statusLogs', 's')
            ->addSelect('a')
            ->addSelect('c')
            ->addSelect('p')
            ->addSelect('pr')
            ->addSelect('s')
        ;

        if (is_array($term['search'])) {

            foreach ($term['search'] as $key => $value) {
                switch ($key) {
                    case 'dateStart':

                        if (isset($value['start']) && !empty($value['start'])) {
                            $qb->andWhere("a.createDate >= :dateStart0");
                            $qb->setParameter('dateStart0', (new \DateTime($value['start'])));
                        }
                        if (isset($value['end']) && !empty($value['end'])) {
                            $qb->andWhere("a.createDate <= :dateStart1");
                            $qb->setParameter('dateStart1', (new \DateTime($value['end'] . ' 23:59:59')));
                        }
                        break;

                    case 'dateDelivery':

                        if (isset($value['start']) && !empty($value['start'])) {
                            $qb->andWhere("l.confirmedDate >= :dateConfirmed0");
                            $qb->setParameter('dateConfirmed0', (new \DateTime($value['start'])));
                        }
                        if (isset($value['end']) && !empty($value['end'])) {
                            $qb->andWhere("l.confirmedDate <= :dateConfirmed1");
                            $qb->setParameter('dateConfirmed1', (new \DateTime($value['end'] . ' 23:59:59')));
                        }
                        break;

                    case 'archived':
                        $qb->andWhere("l.archived = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                    case 'deleted':
                        $qb->andWhere("l.deleted = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                    case 'agreementLineId':
                        $qb->andWhere("l.id = :{$key}");
                        $qb->setParameter($key, $value);
                        break;
                    case 'ownedBy':
                        $customers = $value->getCustomers();
                        if (!empty($customers)) {
                            $qb->andWhere("c.id IN (:{$key})");
                            $qb->setParameter($key, $customers);
                        }
                        break;
                    case 'status':
                        if (!empty($value)) {
                            $qb->andWhere("l.status IN (:{$key})");
                            $qb->setParameter($key, $value);
                        }
                        break;
                    case 'hideArchive':
                        if ($value) {
                            $qb->andWhere("l.status NOT IN (:{$key})");
                            $qb->setParameter($key, [AgreementLine::STATUS_DELETED, AgreementLine::STATUS_ARCHIVED, AgreementLine::STATUS_WAREHOUSE]);
                        }
                        break;
                    case 'q':
                        $qb->andWhere("a.orderNumber Like :q OR c.name Like :q OR p.name Like :q OR c.first_name Like :q OR c.last_name Like :q");
                        $qb->setParameter('q', '%'.$value.'%');
                }
            }
        
        }

        if (isset($term['search']['meta']['sort']) && !empty($term['search']['meta']['sort'])) {
            list($sort, $order) = explode(':', $term['search']['meta']['sort']);

            if ($sort && $order) {
                $qb->orderBy($sort, $order);
            }

        }

        return $qb->getQuery();
    }

    /**
     * Licznik zamówień wg statusu
     *
     * @return mixed
     */
    public function getSummary()
    {

        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.deleted = 0')
            ->select('l.status as statusId, COUNT(l.id) as ordersCount')
            ->groupBy('l.status')
        ;

        // gdy użytkownik ma rolę 'klient' to zawężamy wyniki do podpiętych klientów
        if ($this->security->isGranted('ROLE_CUSTOMER')) {
            $customers = $this->security->getUser()->getCustomers();
            $qb
                ->innerJoin('l.Agreement', 'a')
                ->innerJoin('a.Customer', 'c')

                ->andWhere('c.id IN (:ownedCustomers)')
                ->setParameter('ownedCustomers', $customers)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return AgreementLine[] Returns an array of AgreementLine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AgreementLine
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
