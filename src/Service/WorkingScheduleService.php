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
    public function __construct(?EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function initialize($day)
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $day);
        $valid = \DateTimeImmutable::getLastErrors();
        if ($valid['warning_count'] > 0 || $valid['error_count'] > 0) {
            throw new \Exception('Nieprawidłowy format daty');
        }

        $range = self::getTimeRange($date);
        $this->start = $range['start'];
        $this->end = $range['end'];
    }

    public static function getTimeRange(\DateTimeImmutable $date) {
        return [
            'start' => $date->modify('first day of this month'),
            'end' => $date->modify('last day of this month'),
        ];
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

    public function initializeHolidays()
    {
        foreach ($this->getDefaultNotWorkingDays() as $date) {
            $holiday = new WorkingSchedule();
            $holiday->setIsWorking(false);
            $holiday->setDate(new \DateTime($date));
            $this->entityManager->persist($holiday);
        }
        $this->entityManager->flush();
    }

    public function getDefaultNotWorkingDays()
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

    public function hasHolidaysInitialized(): bool
    {
        if (!$this->scheduledDays) {
            $this->loadDays();
        }
        return count($this->scheduledDays) > 0;
    }

    public function getWorkingDaysCount()
    {
        if (!$this->scheduledDays) {
            $this->loadDays();
        }
        $totalDays = $this->end->diff($this->start)->format("%a");

        $notWorkingDays = array_filter($this->scheduledDays, function(WorkingSchedule $day) {
            return $day->getIsWorking() === false;
        });

        $result = $totalDays - count($notWorkingDays);

        return $result > 0 ? $result : 0;
    }

    public function isWorkingDay($date)
    {
        if (!$this->scheduledDays) {
            $this->loadDays();
        }

        if (false === isset($this->scheduledDaysByDate[$date])) {
            return true;
        }

        return $this->scheduledDaysByDate[$date]->getIsWorking();
    }
}