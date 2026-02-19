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

    /**
     * Test End2End sprawdzający że cały stack (kontroler → serwis → baza) działa poprawnie.
     * Szczegółowe scenariusze są testowane w testach jednostkowych serwisu.
     */
    public function testShouldGetWeekCapacityScheduleData()
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        // Prosty scenariusz: tydzień z capacity, agreement lines i świętem
        $this->createAgreementLineRM(1, 'AL-1', new \DateTime('2026-02-03'),
            AgreementLine::STATUS_MANUFACTURING, 0.5, false, false, true
        );
        $this->createAgreementLineRM(2, 'AL-2', new \DateTime('2026-02-05'),
            AgreementLine::STATUS_WAREHOUSE, 0.7, false, false, true
        );

        $this->createCapacity(new \DateTime('2026-02-03'), 2.0);
        $this->createHoliday(new \DateTime('2026-02-07')); // Sobota
        $this->createHoliday(new \DateTime('2026-02-08')); // Niedziela

        $em->flush();
        $em->clear();

        // When - zakres: 3 dni (03-05.02)
        $client->xmlHttpRequest(
            'GET',
            '/reports/production/week-capacity-schedule?startDate=2026-02-03&endDate=2026-02-05'
        );

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        // Weryfikacja podstawowa - że stack działa
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertIsArray($content);
        $this->assertCount(3, $content, 'Powinno zwrócić 3 dni');

        // Sprawdź strukturę odpowiedzi
        foreach ($content as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('capacity', $day);
            $this->assertArrayHasKey('capacityBurned', $day);
            $this->assertArrayHasKey('agreementLines', $day);
            $this->assertIsArray($day['agreementLines']);
        }

        // Weryfikacja że dane są poprawne (podstawowa)
        $this->assertEquals('2026-02-03', $content[0]['date']);
        $this->assertEquals('2026-02-04', $content[1]['date']);
        $this->assertEquals('2026-02-05', $content[2]['date']);

        // Sprawdź że capacity burned to suma factors (0.5 + 0.7 = 1.2)
        $this->assertEquals(1.2, $content[0]['capacityBurned']);

        // Sprawdź że agreementLines są zwracane
        $this->assertCount(2, $content[0]['agreementLines']);
    }

    // Helper methods

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
