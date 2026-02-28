<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\System\Test\ApiTestCase;

class ScheduleControllerTest extends BaseScheduleReportsTest
{
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
    public function testShouldScheduleCapacity()
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        // Prosty scenariusz: tydzień z capacity, agreement lines i świętem
        $this->createAgreementLineRM(
            1,
            'AL-1',
            new \DateTime('2026-02-03'),
            AgreementLine::STATUS_MANUFACTURING,
            0.5,
            false,
            false,
            true
        );
        $this->createAgreementLineRM(
            2,
            'AL-2',
            new \DateTime('2026-02-05'),
            AgreementLine::STATUS_WAREHOUSE,
            0.7,
            false,
            false,
            true
        );

        $this->createCapacity(new \DateTime('2026-02-03'), 2.0);
        $this->createHoliday(new \DateTime('2026-02-07')); // Sobota
        $this->createHoliday(new \DateTime('2026-02-08')); // Niedziela

        $em->flush();
        $em->clear();

        // When - zakres: 3 dni (03-05.02)
        $client->xmlHttpRequest(
            'GET',
            '/reports/schedule/capacity?startDate=2026-02-03&endDate=2026-02-05'
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
}
