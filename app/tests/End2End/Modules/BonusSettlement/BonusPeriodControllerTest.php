<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\ValueObject\BonusPeriodStatus;
use App\System\Test\ApiTestCase;

class BonusPeriodControllerTest extends ApiTestCase
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

    public function testShouldCreatePeriod(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));

        // When
        $this->postPeriod($client, 2026, 8);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $period = $this->periodRepository()->findOneBy(['year' => 2026, 'month' => 8]);
        $this->assertNotNull($period);
        $this->assertSame(BonusPeriodStatus::OPEN, $period->getStatus());
        $this->assertSame(5, $period->getToleranceDays());
        $this->assertNull($period->getCalculatedAt());
    }

    public function testShouldRejectDuplicatePeriod(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));
        $this->postPeriod($client, 2026, 8);

        // When
        $this->postPeriod($client, 2026, 8);

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('już istnieje', $this->responseBody($client)['error']);
    }

    public function testShouldRejectInvalidMonth(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));

        // When
        $this->postPeriod($client, 2026, 13);

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectMissingPayload(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));

        // When
        $client->request('POST', '/bonus-settlement/periods', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldListPeriodsNewestFirst(): void
    {
        // Given
        // Jeden użytkownik z obydwoma grantami: ponowne login() na tym samym kliencie nie
        // podmienia tokenu, więc drugie logowanie w jednym teście jest pułapką.
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']));
        $this->postPeriod($client, 2026, 7);
        $this->postPeriod($client, 2026, 8);

        // When
        $client->request('GET', '/bonus-settlement/periods');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $periods = $this->responseBody($client)['data'];
        $this->assertSame(8, $periods[0]['month']);
        $this->assertSame(7, $periods[1]['month']);
        $this->assertSame('OPEN', $periods[0]['status']);
        $this->assertNull($periods[0]['closedAt']);
    }

    public function testShouldReturnPeriodWithoutEntries(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']));
        $this->postPeriod($client, 2026, 8);
        $this->getManager()->clear();
        $periodId = $this->periodRepository()->findOneBy(['year' => 2026, 'month' => 8])->getId();

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $periodId);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $period = $this->responseBody($client)['data'];
        $this->assertSame(2026, $period['year']);
        $this->assertSame([], $period['entries']);
        $this->assertSame(0, $period['totalEffective']);
    }

    public function testShouldReturn404ForUnknownPeriod(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.view']));

        // When
        $client->request('GET', '/bonus-settlement/periods/999999');

        // Then
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectCreationWithoutManageGrant(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.view']));

        // When
        $this->postPeriod($client, 2026, 8);

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectListingWithoutViewGrant(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->request('GET', '/bonus-settlement/periods');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    private function postPeriod($client, int $year, int $month): void
    {
        $client->request(
            'POST',
            '/bonus-settlement/periods',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['year' => $year, 'month' => $month])
        );
    }

    private function responseBody($client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }

    private function periodRepository()
    {
        return $this->getManager()->getRepository(BonusPeriod::class);
    }
}
