<?php

namespace App\Tests\End2End\Modules\Reports;

use App\Entity\AgreementLine;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\System\Test\ApiTestCase;

class WeekCapacityScheduleControllerTest extends ApiTestCase
{
    static int $nextId = 1;
    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldRoundDaysUpToThisWeekMondayAndThisWeekSunday(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/reports/production/week-capacity-schedule?startDate=2026-01-01&endDate=2026-01-08');

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $content);
        $this->assertEquals('2025-12-29', $content[0]['start']);
        $this->assertEquals('2026-01-04', $content[0]['end']);
        $this->assertEquals('2026-01-05', $content[1]['start']);
        $this->assertEquals('2026-01-11', $content[1]['end']);

    }

    public function testShouldGetAgreementLineRMData()
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $this->createAgreementLineRM(1, 'ID-1', new \DateTime('2026-02-02'),
            AgreementLine::STATUS_MANUFACTURING, 0.5, false, false, true
        );
        $this->createAgreementLineRM(2, 'ID-2', new \DateTime('2026-02-05'),
            AgreementLine::STATUS_WAREHOUSE, 0.7, false, false, true
        );
        $this->createAgreementLineRM(3, 'ID-3', new \DateTime('2026-01-31'),
            AgreementLine::STATUS_WAREHOUSE, 1, false, false, true
        );
        $this->createAgreementLineRM(4, 'ID-4', new \DateTime('2026-02-15'),
            AgreementLine::STATUS_WAREHOUSE, 1, false, false, true
        );
        // WorkCapacity: 2 zmiany wartości w bazie
        $this->createCapacity(new \DateTime('2026-02-03'), 2);  // Od 03.02 capacity = 2
        $this->createCapacity(new \DateTime('2026-02-05'), 3);  // Od 05.02 capacity = 3

        // Święta: 2 dni
        $this->createHoliday(new \DateTime('2026-02-07')); // Sobota
        $this->createHoliday(new \DateTime('2026-02-08')); // Niedziela
        $em->flush();
        $em->clear();

        // When - zakres: poniedziałek 02.02 do niedzieli 08.02 (1 tydzień)
        $client->xmlHttpRequest(
            'GET',
            '/reports/production/week-capacity-schedule?startDate=2026-02-02&endDate=2026-02-08'
        );

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(2, $content[0]['agreementLines']);
        $this->assertEquals('ID-1', $content[0]['agreementLines'][0]['orderNumber']);
        $this->assertEquals('ID-2', $content[0]['agreementLines'][1]['orderNumber']);
        $this->assertEquals(1.2, $content[0]['capacityBurned']);
        $this->assertEquals(11.5238, $content[0]['capacity']);

        // todo: tu jesteśmy

    }

    private function createAgreementLineRM(
        int $id,
        string $orderNumber,
        \DateTimeInterface $confirmedDate,
        string $status,
        float $factor,
        bool $isDeleted,
        bool $isArchived,
        bool $hasProduction,
    ): AgreementLineRM
    {
        $faker = \Faker\Factory::create();
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
            $production = new \App\Module\AgreementLine\Entity\ProductionRM(departmentSlug: 'dpt01');
            $rm->setProductions([$production]);
        }

        $this->getManager()->persist($rm);
        return $rm;
    }

    private function createCapacity(\DateTimeInterface $dateFrom, float $capacity): WorkCapacity
    {
        $capacity = new WorkCapacity($dateFrom, $capacity);
        $this->getManager()->persist($capacity);
        return $capacity;
    }

    private function createHoliday(\DateTimeInterface $date): void
    {
        $holiday = new WorkSchedule($date, ScheduleDayType::Holiday);
        $this->getManager()->persist($holiday);
    }
}