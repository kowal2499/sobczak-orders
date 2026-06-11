<?php

namespace App\Module\Agreement\Repository;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\Interface\AgreementLineRMRepositoryInterface;
use App\Module\Production\ValueObject\DepartmentEnum;
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

    /**
     * Agregat miernika "Orders Pending": linie rozpoczęte do końca zakresu i jeszcze niezakończone.
     * Dolna granica (start) jest celowo pomijana — zgodnie z dotychczasowym zachowaniem miernika.
     *
     * @return array{factors_summary: string|float|null, count: int|string}
     */
    public function getPendingSummary(\DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('l')
            ->select('SUM(l.factor) as factors_summary')
            ->addSelect('COUNT(l.agreementLineId) as count')
            ->where('l.isDeleted = 0')
            ->andWhere('l.productionEndDate IS NULL')
            ->andWhere('l.productionStartDate <= :end')
            ->setParameter('end', \DateTime::createFromInterface($end)->setTime(23, 59, 59))
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Agregat miernika "Orders Finished": linie zakończone w zakresie (i wcześniej rozpoczęte).
     * Gdy podano $customerIds, wynik jest ograniczony do tych klientów (filtr ROLE_CUSTOMER).
     *
     * @param int[]|null $customerIds
     * @return array{factors_summary: string|float|null, count: int|string}
     */
    public function getFinishedSummary(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?array $customerIds = null
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->select('SUM(l.factor) as factors_summary')
            ->addSelect('COUNT(l.agreementLineId) as count')
            ->where('l.isDeleted = 0')
            ->andWhere('l.productionStartDate IS NOT NULL')
            ->andWhere('l.productionEndDate >= :start')
            ->andWhere('l.productionEndDate <= :end')
            ->setParameter('start', \DateTime::createFromInterface($start)->setTime(0, 0, 0))
            ->setParameter('end', \DateTime::createFromInterface($end)->setTime(23, 59, 59));

        if ($customerIds !== null) {
            if (empty($customerIds)) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('l.customerId IN (:customerIds)')
                    ->setParameter('customerIds', $customerIds);
            }
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Linie dla szczegółów miernika "Orders Pending": rozpoczęte do końca zakresu i niezakończone.
     * Dolna granica jest pomijana (zgodnie z zachowaniem miernika). Gdy $end jest null — bez filtra dat.
     *
     * @return AgreementLineRM[]
     */
    public function findPendingDetailLines(?\DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.isDeleted = 0')
            ->andWhere('l.productionEndDate IS NULL');

        if ($end !== null) {
            $qb->andWhere('l.productionStartDate <= :end')
                ->setParameter('end', \DateTime::createFromInterface($end)->setTime(23, 59, 59));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Linie dla szczegółów miernika "Orders Finished": zakończone w zakresie (i wcześniej rozpoczęte).
     * Gdy podano $customerIds, wynik jest ograniczony do tych klientów (filtr ROLE_CUSTOMER).
     *
     * @param int[]|null $customerIds
     * @return AgreementLineRM[]
     */
    public function findFinishedDetailLines(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?array $customerIds = null
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->where('l.isDeleted = 0')
            ->andWhere('l.productionStartDate IS NOT NULL')
            ->andWhere('l.productionEndDate >= :start')
            ->andWhere('l.productionEndDate <= :end')
            ->setParameter('start', \DateTime::createFromInterface($start)->setTime(0, 0, 0))
            ->setParameter('end', \DateTime::createFromInterface($end)->setTime(23, 59, 59));

        if ($customerIds !== null) {
            if (empty($customerIds)) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('l.customerId IN (:customerIds)')
                    ->setParameter('customerIds', $customerIds);
            }
        }

        return $qb->getQuery()->getResult();
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
                    // Default semantics: at least one non-ghost production task.
                    // Legacy RMs without the `isGhost` field are treated as non-ghost.
                    $qb->andWhere("l.productions != '[]'");
                    $qb->andWhere(
                        "(l.productions LIKE :hasNonGhost1 OR l.productions NOT LIKE :hasGhostKey)"
                    );
                    $qb->setParameter('hasNonGhost1', '%"isGhost":false%');
                    $qb->setParameter('hasGhostKey', '%"isGhost"%');
                    break;
                case 'hasProductionIncludingGhost':
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
                case 'dptDateRange':
                    if (
                        !isset($value['start'], $value['end'], $value['departments'])
                        || !is_array($value['departments'])
                        || empty($value['departments'])
                        || \DateTime::createFromFormat('Y-m-d', $value['start']) === false
                        || \DateTime::createFromFormat('Y-m-d', $value['end']) === false
                    ) {
                        break;
                    }
                    $allowedDpts = array_map(
                        fn (DepartmentEnum $dept) => $dept->value,
                        DepartmentEnum::getProductionDepartments()
                    );
                    $departments = array_values(array_intersect($allowedDpts, $value['departments']));
                    if (empty($departments)) {
                        $qb->andWhere('1 = 0');
                        break;
                    }
                    $rangeStart = new \DateTime($value['start'] . ' 00:00:00');
                    $rangeEnd = new \DateTime($value['end'] . ' 23:59:59');
                    $orx = $qb->expr()->orX();
                    foreach ($departments as $dpt) {
                        $startCol = 'l.' . $dpt . 'StartDate';
                        $endCol = 'l.' . $dpt . 'EndDate';
                        $orx->add($qb->expr()->andX(
                            $qb->expr()->isNotNull($startCol),
                            $qb->expr()->isNotNull($endCol),
                            $qb->expr()->lte($startCol, ':dptRangeEnd'),
                            $qb->expr()->gte($endCol, ':dptRangeStart'),
                        ));
                    }
                    $qb->andWhere($orx);
                    $qb->setParameter('dptRangeStart', $rangeStart);
                    $qb->setParameter('dptRangeEnd', $rangeEnd);
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
