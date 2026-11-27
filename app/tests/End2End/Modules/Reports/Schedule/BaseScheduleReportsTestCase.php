<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\System\Test\ApiTestCase;
use Faker\Factory;

abstract class BaseScheduleReportsTestCase extends ApiTestCase
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

    /**
     * @param array<int, array{slug: string, start: string, end: string, status: string, isGhost: bool, id: int}> $productions
     */
    protected function makeLineWithProductionTasks(
        int $id,
        string $orderNumber,
        int $customerId,
        array $productions,
        bool $hasProduction = true,
    ): AgreementLineRM {
        $faker = Factory::create();
        $em = $this->getManager();

        $confirmedDate = new \DateTime($productions[0]['start']);

        $rm = new AgreementLineRM($id);
        $rm->setConfirmedDate($confirmedDate);
        $rm->setStatus(AgreementLine::STATUS_MANUFACTURING);
        $rm->setIsDeleted(false);
        $rm->setIsArchived(false);
        $rm->setHasProduction($hasProduction);
        $rm->setOrderNumber($orderNumber);
        $rm->setQ($faker->text(30));
        $rm->setCustomerName($faker->name);
        $rm->setProductName($faker->word);
        $rm->setAgreementCreateDate((clone $confirmedDate)->modify('-1 week'));
        $rm->setAgreementId($faker->randomDigit());
        $rm->setCustomerId($customerId);
        $rm->setFactor(1.0);

        $prodModels = [];
        $byDpt = [];
        foreach ($productions as $p) {
            $prod = new ProductionRM(departmentSlug: $p['slug']);
            $prod->setId($p['id']);
            $prod->setDateStart(new \DateTime($p['start']));
            $prod->setDateEnd(new \DateTime($p['end']));
            $prod->setStatus($p['status']);
            $prod->setIsGhost($p['isGhost']);
            $prodModels[] = $prod;
            // Pierwsza produkcja per dpt populuje pola dpt*StartDate/EndDate (zgodnie z UpdateAgreementLineRMHandler)
            if (!isset($byDpt[$p['slug']])) {
                $byDpt[$p['slug']] = $prod;
            }
        }
        $rm->setProductions($prodModels);

        $setters = [
            'dpt01' => ['setDpt01StartDate', 'setDpt01EndDate'],
            'dpt02' => ['setDpt02StartDate', 'setDpt02EndDate'],
            'dpt03' => ['setDpt03StartDate', 'setDpt03EndDate'],
            'dpt04' => ['setDpt04StartDate', 'setDpt04EndDate'],
            'dpt05' => ['setDpt05StartDate', 'setDpt05EndDate'],
            'dpt06' => ['setDpt06StartDate', 'setDpt06EndDate'],
        ];
        foreach ($setters as $slug => [$setStart, $setEnd]) {
            if (isset($byDpt[$slug])) {
                $rm->{$setStart}($byDpt[$slug]->getDateStart());
                $rm->{$setEnd}($byDpt[$slug]->getDateEnd());
            } else {
                $rm->{$setStart}(null);
                $rm->{$setEnd}(null);
            }
        }

        $em->persist($rm);
        return $rm;
    }
}
