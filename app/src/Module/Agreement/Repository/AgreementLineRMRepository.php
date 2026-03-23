<?php

namespace App\Module\Agreement\Repository;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\Interface\AgreementLineRMRepositoryInterface;
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
class AgreementLineRMRepository extends ServiceEntityRepository implements AgreementLineRMRepositoryInterface
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

    public function search(?array $criteria)
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.isDeleted = 0');

        // Jeżeli nie wskazano statusu (tzn. chcemy widzieć wszystkie zamówienia), to ukryj zamówienia usunięte
        if (false === isset($criteria['search']['status'])) {
            $criteria['search']['hideDeleted'] = true;
        }

        foreach ($criteria['search'] as $key => $value) {
            switch ($key) {
                case 'hasProduction':
                    $qb->andWhere("l.productions != '[]'");
                    break;
                case 'dateStart':
                    if (
                        isset($value['start'])
                        && (\DateTime::createFromFormat('Y-m-d', $value['start']) !== false)
                    ) {
                        $qb->andWhere("l.agreementCreateDate >= :dateStart0");
                        $qb->setParameter('dateStart0', new \DateTime($value['start'] . ' 23:59:59'));
                    }
                    if (
                        isset($value['end'])
                        && (\DateTime::createFromFormat('Y-m-d', $value['end']) !== false)
                    ) {
                        $qb->andWhere("l.agreementCreateDate <= :dateStart1");
                        $qb->setParameter('dateStart1', new \DateTime($value['end'] . ' 23:59:59'));
                    }
                    break;
                case 'dateDelivery':
                    if (
                        isset($value['start'])
                        && (\DateTime::createFromFormat('Y-m-d', $value['start']) !== false)
                    ) {
                        $qb->andWhere("l.confirmedDate >= :dateConfirmed0");
                        $qb->setParameter('dateConfirmed0', (new \DateTime($value['start'])));
                    }
                    if (
                        isset($value['end']) && (\DateTime::createFromFormat('Y-m-d', $value['end']) !== false)
                    ) {
                        $qb->andWhere("l.confirmedDate <= :dateConfirmed1");
                        $qb->setParameter('dateConfirmed1', (new \DateTime($value['end'] . ' 23:59:59')));
                    }
                    break;
                case 'archived':
                    $qb->andWhere("l.isArchived = :$key");
                    $qb->setParameter($key, $value);
                    break;
                case 'agreementLineId':
                    $qb->andWhere("l.agreementLineId = :$key");
                    $qb->setParameter($key, $value);
                    break;
                case 'ownedBy':
                    $customers = $value->getCustomers()->toArray();
                    if (!empty($customers)) {
                        $qb->andWhere("l.customerId IN (:$key)");
                        $qb->setParameter(
                            $key,
                            array_filter(array_map(fn (?Customer $customer) => $customer?->getId(), $customers))
                        );
                    }
                    break;
                case 'status':
                    if (!empty($value)) {
                        $qb->andWhere("l.status IN (:$key)");
                        $qb->setParameter($key, $value);
                    }
                    break;
                case 'statusNot':
                    if (!empty($value)) {
                        $qb->andWhere("l.status NOT IN (:$key)");
                        $qb->setParameter($key, $value);
                    }
                    break;
                case 'hideArchive':
                    if ($value) {
                        $qb->andWhere("l.status NOT IN (:$key)");
                        $qb->setParameter($key, [
                            AgreementLine::STATUS_DELETED,
                            AgreementLine::STATUS_ARCHIVED,
                            AgreementLine::STATUS_WAREHOUSE
                        ]);
                    }
                    break;
                case 'hideDeleted':
                    $qb->andWhere("l.status NOT IN (:$key)");
                    $qb->setParameter($key, [AgreementLine::STATUS_DELETED]);
                    break;
                case 'q':
                    $qb->andWhere("l.q Like :q");
                    $qb->setParameter('q', '%' . $value . '%');
                    break;
            }
        }

        if (!empty($criteria['search']['sort'])) {
            $sort = preg_replace('/_.+$/', '', $criteria['search']['sort']);
            $order = strtoupper(preg_replace('/^.+_/', '', $criteria['search']['sort']));
            $order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';

            switch ($sort) {
                case 'id':
                    $qb->orderBy('l.orderNumber', $order);
                    break;
                case 'dateReceive':
                    $qb->orderBy('l.agreementCreateDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dateConfirmed':
                    $qb->orderBy('l.confirmedDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'customer':
                    $qb->orderBy('l.customerName', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'product':
                    $qb->orderBy('l.productName', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'factor':
                    $qb->orderBy('l.factor', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'user':
                    $qb->orderBy('l.userName', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt01DateStart':
                    $qb->orderBy('l.dpt01StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt01DateEnd':
                    $qb->orderBy('l.dpt01EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt02DateStart':
                    $qb->orderBy('l.dpt02StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt02DateEnd':
                    $qb->orderBy('l.dpt02EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt03DateStart':
                    $qb->orderBy('l.dpt03StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt03DateEnd':
                    $qb->orderBy('l.dpt03EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt04DateStart':
                    $qb->orderBy('l.dpt04StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt04DateEnd':
                    $qb->orderBy('l.dpt04EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt05DateStart':
                    $qb->orderBy('l.dpt05StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt05DateEnd':
                    $qb->orderBy('l.dpt05EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt06DateStart':
                    $qb->orderBy('l.dpt06StartDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
                case 'dpt06DateEnd':
                    $qb->orderBy('l.dpt06EndDate', $order)
                        ->addOrderBy('l.agreementLineId', $order);
                    break;
//                    todo: departmentDates
            }
        }

        return $qb->getQuery();
    }
}
