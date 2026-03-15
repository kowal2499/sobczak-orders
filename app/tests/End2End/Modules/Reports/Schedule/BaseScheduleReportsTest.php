<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\System\Test\ApiTestCase;
use Faker\Factory;

abstract class BaseScheduleReportsTest extends ApiTestCase
{
    protected function createAgreementLineRM(
        int $id,
        string $orderNumber,
        \DateTimeInterface $confirmedDate,
        string $status,
        float $factor,
        bool $isDeleted,
        bool $isArchived,
        bool $hasProduction,
    ): AgreementLineRM {
        $faker = Factory::create();
        $rm = new AgreementLineRM($id);
        $rm->setConfirmedDate($confirmedDate);
        $rm->setStatus($status);
        $rm->setIsDeleted($isDeleted);
        $rm->setIsArchived($isArchived);
        $rm->setHasProduction($hasProduction);
        $rm->setOrderNumber($orderNumber);
        $rm->setQ($faker->text(30));
        $rm->setCustomerName($faker->name);
        $rm->setAgreementCreateDate((clone $confirmedDate)->modify('-1 week'));
        $rm->setAgreementId($faker->randomDigit());
        $rm->setCustomerId($faker->randomDigit());
        $rm->setFactor($factor);
        if ($hasProduction) {
            $production = new \App\Module\Agreement\ReadModel\ProductionRM(departmentSlug: 'dpt01');
            $rm->setProductions([$production]);
        }
        $this->getManager()->persist($rm);
        return $rm;
    }

    protected function createCapacity(\DateTimeInterface $dateFrom, float $capacity): WorkCapacity
    {
        $capacity = new WorkCapacity($dateFrom, $capacity);
        $this->getManager()->persist($capacity);
        return $capacity;
    }

    protected function createHoliday(\DateTimeInterface $date): void
    {
        $holiday = new WorkSchedule($date, ScheduleDayType::Holiday);
        $this->getManager()->persist($holiday);
    }
}
