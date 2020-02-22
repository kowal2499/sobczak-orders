<?php


namespace App\Service;


use App\Entity\WorkingSchedule;
use Doctrine\ORM\EntityManagerInterface;

class WorkingScheduleService
{
    private $start;
    private $end;

    private const additionalHolidays = [
        '01' => ['01', '06'],
        '05' => ['01', '03'],
        '08' => ['15'],
        '11' => ['01'],
        '12' => ['25', '26'],
    ];

    private $entityManager;
    private $scheduledDays = [];
    private $scheduledDaysByDate = [];

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param String $day 'Y-m-d'
     * @throws \Exception
     */
    public function initialize($day)
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $day);
        $valid = \DateTimeImmutable::getLastErrors();
        if ($valid['warning_count'] > 0 || $valid['error_count'] > 0) {
            throw new \Exception('Nieprawidłowy format daty');
        }

        // ustalenie granic miesiąca
        $this->start = $date->modify('first day of this month')->setTime(0, 0, 0);
        $this->end = $date->modify('last day of this month')->setTime(23, 59, 59, 999999);

        // załadowanie dni z bazy
        $this->loadDays();

        // przygotowanie miesiąca jeśli w bazie nic nie ma
        if (count($this->scheduledDays) === 0) {
            $this->initializeHolidays();
            $this->loadDays();
        }
    }

    private function loadDays()
    {
        /* @var \App\Repository\WorkingScheduleRepository $repository */
        $repository = $this->entityManager->getRepository(WorkingSchedule::class);
        $this->scheduledDays = $repository->findByRange($this->start, $this->end);

        foreach ($this->scheduledDays as $item) {
            $this->scheduledDaysByDate[$item->getDate()->format('Y-m-d')] = $item;
        }
    }

    /**
     * Zapisuje w bazie danych domyślne wolne dni
     * @throws \Exception
     */
    private function initializeHolidays()
    {
        foreach ($this->getDefaultNotWorkingDays() as $date) {
            $holiday = new WorkingSchedule();
            $holiday->setIsWorking(false);
            $holiday->setDate(new \DateTime($date));
            $this->entityManager->persist($holiday);
        }
        $this->entityManager->flush();
    }

    private function getDefaultNotWorkingDays()
    {
        $holidays = [];
        $walk = new \DateTime($this->start->format('Y-m-d'));
        while ($walk <= $this->end) {
            $dayName = $walk->format('D');
            if ($dayName === 'Sat' || $dayName === 'Sun') {
                $holidays[] = $walk->format('Y-m-d');
            }
            $walk->modify('+1 day');
        }

        if (in_array($this->start->format('m'), array_keys(self::additionalHolidays))) {

            foreach (self::additionalHolidays[$this->start->format('m')] as $day) {
                $newHoliday = "{$this->start->format('Y')}-{$this->start->format('m')}-{$day}";
                if (\DateTime::createFromFormat('Y-m-d', $newHoliday) >= $this->start &&
                    \DateTime::createFromFormat('Y-m-d', $newHoliday) <= $this->end) {
                    $holidays[] = $newHoliday;
                }
            }
        }
        sort($holidays);
        return array_values(array_unique($holidays));
    }

    public function getWorkingDaysCount()
    {
        $totalDays = $this->end->diff($this->start)->format("%a");

        $notWorkingDays = array_filter($this->scheduledDays, function(WorkingSchedule $day) {
            return $day->getIsWorking() === false;
        });

        $result = $totalDays - count($notWorkingDays);

        return $result > 0 ? $result : 0;
    }

    public function isWorkingDay($date)
    {
        if (false === isset($this->scheduledDaysByDate[$date])) {
            return true;
        }

        return $this->scheduledDaysByDate[$date]->getIsWorking();
    }

    public function getNotWorkingDays()
    {
        $notWorkingDays = array_filter($this->scheduledDays, function(WorkingSchedule $day) {
            return $day->getIsWorking() === false;
        });

        return array_map(function ($day) {
            return $day->getDate()->format('Y-m-d');
        }, $notWorkingDays);
    }

    /**
     * Zapisuje dzień jako wolny od pracy
     * @param $date
     * @throws \Exception
     */
    public function setNotWorkingDay($date) {
        if (true === isset($this->scheduledDaysByDate[$date])) {
            $this->scheduledDaysByDate[$date]->setIsWorking(false);
            $this->entityManager->persist($this->scheduledDaysByDate[$date]);
            $this->entityManager->flush();
            return;
        }

        $holiday = new WorkingSchedule();
        $holiday->setIsWorking(false);
        $holiday->setDate(new \DateTime($date));
        $this->entityManager->persist($holiday);
        $this->entityManager->flush();

        $this->loadDays();
    }

    /**
     * Zapisuje dzień jako pracujący
     * @param $date
     */
    public function setWorkingDay($date) {
        if (false === isset($this->scheduledDaysByDate[$date])) {
            return;
        }

        $this->scheduledDaysByDate[$date]->setIsWorking(true);
        $this->entityManager->persist($this->scheduledDaysByDate[$date]);
        $this->entityManager->flush();
    }
}