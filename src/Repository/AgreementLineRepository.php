<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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

    public function __construct(ManagerRegistry $registry, Security $security)
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
            ->leftJoin('s.user', 'u')
            ->addSelect('a')
            ->addSelect('c')
            ->addSelect('p')
            ->addSelect('pr')
            ->addSelect('s')
            ->addSelect('u')
            ->andWhere('l.deleted = 0')     // nigdy nie zwracamy usuniętych zamówień
        ;

        if (is_array($term['search'])) {

            // Jeżeli nie wskazano statusu (tzn chcemy widzieć wszystkie zamówienia), to ukryj zamówienia usunięte
            if (false === isset($term['search']['status'])) {
                $term['search']['hideDeleted'] = true;
            }

            foreach ($term['search'] as $key => $value) {
                switch ($key) {
                    case 'dateStart':

                        if (isset($value['start']) && (\DateTime::createFromFormat('Y-m-d', $value['start']) !== false)) {
                            $qb->andWhere("a.createDate >= :dateStart0");
                            $qb->setParameter('dateStart0', new \DateTime($value['start'] . ' 23:59:59'));
                        }
                        if (isset($value['end']) && (\DateTime::createFromFormat('Y-m-d', $value['end']) !== false)) {
                            $qb->andWhere("a.createDate <= :dateStart1");
                            $qb->setParameter('dateStart1', new \DateTime($value['end'] . ' 23:59:59'));
                        }
                        break;

                    case 'dateDelivery':

                        if (isset($value['start']) && (\DateTime::createFromFormat('Y-m-d', $value['start']) !== false)) {
                            $qb->andWhere("l.confirmedDate >= :dateConfirmed0");
                            $qb->setParameter('dateConfirmed0', (new \DateTime($value['start'])));
                        }
                        if (isset($value['end']) && (\DateTime::createFromFormat('Y-m-d', $value['end']) !== false)) {
                            $qb->andWhere("l.confirmedDate <= :dateConfirmed1");
                            $qb->setParameter('dateConfirmed1', (new \DateTime($value['end'] . ' 23:59:59')));
                        }
                        break;

                    case 'archived':
                        $qb->andWhere("l.archived = :{$key}");
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
                    case 'hideDeleted':
                        $qb->andWhere("l.status NOT IN (:{$key})");
                        $qb->setParameter($key, [AgreementLine::STATUS_DELETED]);
                        break;

                    case 'q':
                        $qb->andWhere("a.orderNumber Like :q OR c.name Like :q OR p.name Like :q OR c.first_name Like :q OR c.last_name Like :q");
                        $qb->setParameter('q', '%'.$value.'%');
                        break;
                }
            }
        
        }

        if (isset($term['search']['sort']) && !empty($term['search']['sort'])) {

            $sort = preg_replace('/_.+$/', '', $term['search']['sort']);
            $order = preg_replace('/^.+_/', '', $term['search']['sort']);

            if ($sort && $order) {
                switch ($sort) {
                    case 'id': $qb->orderBy('a.orderNumber', $order); break;
                    case 'dateReceive': $qb->orderBy('a.createDate', $order); break;
                    case 'dateConfirmed': $qb->orderBy('l.confirmedDate', $order); break;
                    case 'customer': $qb->orderBy('c.name', $order); break;
                    case 'product': $qb->orderBy('p.name', $order); break;
                    case 'factor': $qb->orderBy('l.factor', $order); break;
                }
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
