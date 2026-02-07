<?php

namespace App\Module\WorkingSchedule\Repository;

use App\Module\WorkingSchedule\Entity\WorkingSchedule;
use App\Module\WorkingSchedule\ValueObject\ScheduleDayType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkingSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkingSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkingSchedule[]    findAll()
 * @method WorkingSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkingScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkingSchedule::class);
    }

    public function findHolidaysByRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->findByRange($start, $end, ScheduleDayType::Holiday);
    }

    public function findWorkingDaysByRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->findByRange($start, $end, ScheduleDayType::Working);
    }

    public function findByRange(\DateTimeImmutable $start, \DateTimeImmutable $end, ?ScheduleDayType $dayType = null): array
    {
        $query = $this->createQueryBuilder('w')
            ->andWhere('w.date >= :start')
            ->andWhere('w.date <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($dayType !== null) {
            $query->andWhere('w.dayType = :dayType')
                  ->setParameter('dayType', $dayType);
        }

        return $query->getQuery()->getResult();
    }
}