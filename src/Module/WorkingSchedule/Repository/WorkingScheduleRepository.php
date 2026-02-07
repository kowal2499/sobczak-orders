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
            ->setParameter('end', $end)
            ->orderBy('w.date', 'ASC');

        if ($dayType !== null) {
            $query->andWhere('w.dayType = :dayType')
                  ->setParameter('dayType', $dayType);
        }

        return $query->getQuery()->getResult();
    }

    public function save(WorkingSchedule $workingSchedule, bool $flush = true): void
    {
        $this->_em->persist($workingSchedule);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(WorkingSchedule $workingSchedule, bool $flush = true): void
    {
        $this->_em->remove($workingSchedule);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Upsert - if entry for given date exists, update it, otherwise create new one
     */
    public function upsert(\DateTimeImmutable $date, ScheduleDayType $dayType, ?string $description = null, bool $flush = true): WorkingSchedule
    {
        $existing = $this->findOneBy(['date' => $date]);

        if ($existing) {
            $existing->setDayType($dayType);
            $existing->setDescription($description);
            $workingSchedule = $existing;
        } else {
            $workingSchedule = new WorkingSchedule($date, $dayType, $description);
        }

        $this->save($workingSchedule, $flush);

        return $workingSchedule;
    }
}