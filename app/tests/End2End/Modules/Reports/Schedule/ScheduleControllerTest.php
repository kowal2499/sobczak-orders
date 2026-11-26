<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;

class ScheduleControllerTest extends BaseScheduleReportsTestCase
{
    use CapacityResponseTrait;

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
        $user = $this->createUser([], [], ['reports.calendar_general']);
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
        $content = $this->capacityDays($response);

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

    public function testShouldSendEachWeeksLinesOnceWithOnlyTheFieldsTheSidebarReads(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['reports.calendar_general']));
        $this->createLine(1, 'AL-1', '2026-02-03', 0.5);
        $this->createLine(2, 'AL-2', '2026-02-10', 0.7);
        $this->createCapacity(new \DateTime('2026-02-02'), 2.0);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When - two working weeks
        $client->xmlHttpRequest('GET', '/reports/schedule/capacity?startDate=2026-02-02&endDate=2026-02-13');

        // Then
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(10, $payload['days']);
        $this->assertArrayNotHasKey('agreementLines', $payload['days'][0]);
        $this->assertSame('2026-02-02', $payload['days'][4]['weekStart']);
        $this->assertSame('2026-02-09', $payload['days'][5]['weekStart']);

        $this->assertSame(['2026-02-02', '2026-02-09'], array_keys($payload['agreementLinesByWeek']));
        $this->assertSame(['AL-1'], array_column($payload['agreementLinesByWeek']['2026-02-02'], 'orderNumber'));
        $this->assertSame(['AL-2'], array_column($payload['agreementLinesByWeek']['2026-02-09'], 'orderNumber'));

        $line = $payload['agreementLinesByWeek']['2026-02-02'][0];
        $this->assertSame([
            'agreementLineId', 'orderNumber', 'internalNumber', 'customerName', 'productName', 'status',
            'agreementCreateDate', 'confirmedDate', 'factor', 'userName', 'q', 'productions',
        ], array_keys($line));
    }

    public function testShouldSendNoLinesWithoutCalendarOrWeeklyCapacityGrant(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->createLine(1, 'AL-1', '2026-02-03', 0.5);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/capacity?startDate=2026-02-02&endDate=2026-02-06');

        // Then
        $this->assertSame('{"days":', substr($client->getResponse()->getContent(), 0, 8));
        $this->assertStringEndsWith('"agreementLinesByWeek":{}}', $client->getResponse()->getContent());
    }

    private function createLine(int $id, string $orderNumber, string $confirmedDate, float $factor): void
    {
        $this->createAgreementLineRM(
            $id,
            $orderNumber,
            new \DateTime($confirmedDate),
            AgreementLine::STATUS_MANUFACTURING,
            $factor,
            false,
            false,
            true
        );
    }
}
