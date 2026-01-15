<?php

namespace App\Repository;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

/**
 * @method AgreementLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgreementLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgreementLine[]    findAll()
 * @method AgreementLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgreementLineRepository extends ServiceEntityRepository
{
    private Security $security;

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
            ->leftJoin('a.user', 'au')
            ->addSelect('a')
            ->addSelect('c')
            ->addSelect('p')
            ->addSelect('au')
            ->addSelect("LOWER(CONCAT(au.firstName, ' ', au.lastName)) AS HIDDEN userFullName")
            ->andWhere('l.deleted = 0')     // nigdy nie zwracamy usuniętych zamówień
        ;

        if (isset($term['search']) && is_array($term['search'])) {
            if (isset($term['search']['hasProduction']) && $term['search']['hasProduction']) {
                $qb->innerJoin('l.productions', 'pr')
                    ->leftJoin('pr.statusLogs', 's')
                    ->leftJoin('s.user', 'u')
//                    ->leftJoin('pr.factorAdjustments', 'fa')
                    ->addSelect('pr')
                    ->addSelect('s')
                    ->addSelect('u')
//                    ->addSelect('fa')
                ;
            }

            // Jeżeli nie wskazano statusu (tzn. chcemy widzieć wszystkie zamówienia), to ukryj zamówienia usunięte
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
                        $qb->andWhere("l.archived = :$key");
                        $qb->setParameter($key, $value);
                        break;
                    case 'agreementLineId':
                        $qb->andWhere("l.id = :$key");
                        $qb->setParameter($key, $value);
                        break;
                    case 'ownedBy':
                        $customers = $value->getCustomers();
                        if (!empty($customers)) {
                            $qb->andWhere("c.id IN (:$key)");
                            $qb->setParameter($key, $customers);
                        }
                        break;
                    case 'status':
                        if (!empty($value)) {
                            $qb->andWhere("l.status IN (:$key)");
                            $qb->setParameter($key, $value);
                        }
                        break;
                    case 'hideArchive':
                        if ($value) {
                            $qb->andWhere("l.status NOT IN (:$key)");
                            $qb->setParameter($key, [AgreementLine::STATUS_DELETED, AgreementLine::STATUS_ARCHIVED, AgreementLine::STATUS_WAREHOUSE]);
                        }
                        break;
                    case 'hideDeleted':
                        $qb->andWhere("l.status NOT IN (:$key)");
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
            $order = strtoupper(preg_replace('/^.+_/', '', $term['search']['sort']));
            $order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';

            if ($sort) {
                switch ($sort) {
                    case 'id': $qb->orderBy('a.orderNumber', $order); break;
                    case 'dateReceive': $qb->orderBy('a.createDate', $order); break;
                    case 'dateConfirmed': $qb->orderBy('l.confirmedDate', $order); break;
                    case 'customer': $qb->orderBy('c.name', $order); break;
                    case 'product': $qb->orderBy('p.name', $order); break;
                    case 'factor': $qb->orderBy('l.factor', $order); break;
                    case 'user': $qb->orderBy('userFullName', $order); break;

                    case 'dpt01DateStart':
                        $qb->addSelect('(SELECT prS01.dateStart FROM App\\Entity\\Production prS01 WHERE prS01.agreementLine = l AND prS01.departmentSlug = :slug_dpt01) AS HIDDEN sort_dpt01_start')
                           ->addOrderBy('sort_dpt01_start', $order)
                           ->setParameter('slug_dpt01', TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
                        break;
                    case 'dpt01DateEnd':
                        $qb->addSelect('(SELECT prE01.dateEnd FROM App\\Entity\\Production prE01 WHERE prE01.agreementLine = l AND prE01.departmentSlug = :slug_dpt01) AS HIDDEN sort_dpt01_end')
                           ->addOrderBy('sort_dpt01_end', $order)
                           ->setParameter('slug_dpt01', TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
                        break;
                    case 'dpt02DateStart':
                        $qb->addSelect('(SELECT prS02.dateStart FROM App\\Entity\\Production prS02 WHERE prS02.agreementLine = l AND prS02.departmentSlug = :slug_dpt02) AS HIDDEN sort_dpt02_start')
                           ->addOrderBy('sort_dpt02_start', $order)
                           ->setParameter('slug_dpt02', TaskTypes::TYPE_DEFAULT_SLUG_CNC);
                        break;
                    case 'dpt02DateEnd':
                        $qb->addSelect('(SELECT prE02.dateEnd FROM App\\Entity\\Production prE02 WHERE prE02.agreementLine = l AND prE02.departmentSlug = :slug_dpt02) AS HIDDEN sort_dpt02_end')
                           ->addOrderBy('sort_dpt02_end', $order)
                           ->setParameter('slug_dpt02', TaskTypes::TYPE_DEFAULT_SLUG_CNC);
                        break;
                    case 'dpt03DateStart':
                        $qb->addSelect('(SELECT prS03.dateStart FROM App\\Entity\\Production prS03 WHERE prS03.agreementLine = l AND prS03.departmentSlug = :slug_dpt03) AS HIDDEN sort_dpt03_start')
                           ->addOrderBy('sort_dpt03_start', $order)
                           ->setParameter('slug_dpt03', TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
                        break;
                    case 'dpt03DateEnd':
                        $qb->addSelect('(SELECT prE03.dateEnd FROM App\\Entity\\Production prE03 WHERE prE03.agreementLine = l AND prE03.departmentSlug = :slug_dpt03) AS HIDDEN sort_dpt03_end')
                           ->addOrderBy('sort_dpt03_end', $order)
                           ->setParameter('slug_dpt03', TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
                        break;
                    case 'dpt04DateStart':
                        $qb->addSelect('(SELECT prS04.dateStart FROM App\\Entity\\Production prS04 WHERE prS04.agreementLine = l AND prS04.departmentSlug = :slug_dpt04) AS HIDDEN sort_dpt04_start')
                           ->addOrderBy('sort_dpt04_start', $order)
                           ->setParameter('slug_dpt04', TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING);
                        break;
                    case 'dpt04DateEnd':
                        $qb->addSelect('(SELECT prE04.dateEnd FROM App\\Entity\\Production prE04 WHERE prE04.agreementLine = l AND prE04.departmentSlug = :slug_dpt04) AS HIDDEN sort_dpt04_end')
                           ->addOrderBy('sort_dpt04_end', $order)
                           ->setParameter('slug_dpt04', TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING);
                        break;
                    case 'dpt05DateStart':
                        $qb->addSelect('(SELECT prS05.dateStart FROM App\\Entity\\Production prS05 WHERE prS05.agreementLine = l AND prS05.departmentSlug = :slug_dpt05) AS HIDDEN sort_dpt05_start')
                           ->addOrderBy('sort_dpt05_start', $order)
                           ->setParameter('slug_dpt05', TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING);
                        break;
                    case 'dpt05DateEnd':
                        $qb->addSelect('(SELECT prE05.dateEnd FROM App\\Entity\\Production prE05 WHERE prE05.agreementLine = l AND prE05.departmentSlug = :slug_dpt05) AS HIDDEN sort_dpt05_end')
                           ->addOrderBy('sort_dpt05_end', $order)
                           ->setParameter('slug_dpt05', TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING);
                        break;
                }
            }
        }

        return $qb->getQuery();
    }

    public function getAllFiltered(array $term)
    {
        return $this->getFiltered($term)->getResult();
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
            if (!empty($customers->toArray())) {
                $qb
                    ->innerJoin('l.Agreement', 'a')
                    ->innerJoin('a.Customer', 'c')
                    ->andWhere('c.id IN (:ownedCustomers)')
                    ->setParameter('ownedCustomers', $customers);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithProductionAndStatuses(int $id)
    {
        $manager = $this->getEntityManager();
        $query = $manager->createQuery('
            SELECT l, pr 
            FROM 
                App\Entity\AgreementLine l 
                LEFT JOIN l.productions pr
                LEFT JOIN pr.statusLogs log
            WHERE l.id = :id AND (pr.id IS NULL OR pr.departmentSlug IN (:departmentTypes))
        ')
            ->setParameter('id', $id)
            ->setParameter('departmentTypes', TaskTypes::getDefaultSlugs());

        return $query->getOneOrNullResult();
    }

    public function findWithFactors(array $ids): array
    {
        $manager = $this->getEntityManager();
        $query = $manager->createQuery('
            SELECT l, f 
            FROM 
                App\Entity\AgreementLine l 
                LEFT JOIN l.factors f
            WHERE l.id IN (:ids)
        ')
            ->setParameter('ids', $ids);

        return $query->getResult();
    }

    public function save(AgreementLine $agreementLine, bool $flush = true): void
    {
        $this->_em->persist($agreementLine);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function refresh(AgreementLine $agreementLine): void
    {
        $this->_em->refresh($agreementLine);
    }
}
