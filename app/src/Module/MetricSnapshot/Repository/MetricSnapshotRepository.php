<?php

namespace App\Module\MetricSnapshot\Repository;

use App\Module\MetricSnapshot\Entity\MetricSnapshot;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetricSnapshot>
 */
class MetricSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetricSnapshot::class);
    }

    public function hasAnyForDate(\DateTimeImmutable $day): bool
    {
        return null !== $this->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.snapshotDate = :day')
            ->setParameter('day', $day->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countForDate(\DateTimeImmutable $day): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.snapshotDate = :day')
            ->setParameter('day', $day->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteForDate(\DateTimeImmutable $day): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.snapshotDate = :day')
            ->setParameter('day', $day->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }

    public function findLastComputedAt(): ?\DateTimeImmutable
    {
        $value = $this->createQueryBuilder('s')
            ->select('MAX(s.computedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $value ? null : new \DateTimeImmutable($value);
    }

    /**
     * Sumy wierszy klientów per dzień/miernik/wymiar/okres. Wiersze mierników firmowych
     * przechodzą zawsze, bez względu na filtr klienta.
     *
     * @param SnapshotMetric[] $metrics
     * @param int[]|null $customerIds null = bez filtra
     * @return array<array{
     *     snapshotDate: \DateTimeImmutable,
     *     metric: SnapshotMetric,
     *     dimension: string,
     *     periodKey: string,
     *     source: string,
     *     factorSum: float|string|null,
     *     itemCount: int|string
     * }>
     */
    public function findHistory(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $metrics,
        ?array $customerIds
    ): array {
        if (!$metrics) {
            return [];
        }

        $qb = $this->createQueryBuilder('s')
            ->select('s.snapshotDate', 's.metric', 's.dimension', 's.periodKey')
            ->addSelect('MAX(s.source) AS source')
            ->addSelect('SUM(s.factorSum) AS factorSum', 'SUM(s.itemCount) AS itemCount')
            ->where('s.snapshotDate BETWEEN :from AND :to')
            ->andWhere('s.metric IN (:metrics)')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->setParameter('metrics', array_map(fn (SnapshotMetric $m) => $m->value, $metrics))
            ->groupBy('s.snapshotDate', 's.metric', 's.dimension', 's.periodKey')
            ->orderBy('s.snapshotDate', 'ASC');

        if (null !== $customerIds) {
            $companyWide = array_filter($metrics, fn (SnapshotMetric $m) => $m->isCompanyWide());
            $qb->andWhere('s.customerId IN (:customerIds) OR s.metric IN (:companyWide)')
                ->setParameter('customerIds', $customerIds ?: [-1])
                ->setParameter('companyWide', array_map(fn (SnapshotMetric $m) => $m->value, $companyWide) ?: ['-']);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
