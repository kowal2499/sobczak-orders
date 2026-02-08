<?php

namespace App\Module\WorkConfiguration\Repository;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkSchedule[]    findAll()
 * @method WorkSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkSchedule::class);
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

    public function save(WorkSchedule $workSchedule, bool $flush = true): void
    {
        $this->_em->persist($workSchedule);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function delete(WorkSchedule $workSchedule, bool $flush = true): void
    {
        $this->_em->remove($workSchedule);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Upsert - if entry for given date exists, update it, otherwise create new one
     */
    public function upsert(\DateTimeImmutable $date, ScheduleDayType $dayType, ?string $description = null, bool $flush = true): WorkSchedule
    {
        $existing = $this->findOneBy(['date' => $date]);

        if ($existing) {
            $existing->setDayType($dayType);
            $existing->setDescription($description);
            $workSchedule = $existing;
        } else {
            $workSchedule = new WorkSchedule($date, $dayType, $description);
        }

        $this->save($workSchedule, $flush);

        return $workSchedule;
    }
}