<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\Module\BonusSettlement\ValueObject\BonusPeriodStatus;
use App\System\Test\ApiTestCase;

/**
 * Zamknięcie i ponowne otwarcie okresu oraz niezmienność zamkniętego miesiąca.
 */
class CloseBonusPeriodTest extends ApiTestCase
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

    public function testShouldCloseWithAuthorAndTime(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);

        // When
        $client->request('POST', $this->url($period) . '/close');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $closed = $this->reload($period);
        $this->assertSame(BonusPeriodStatus::CLOSED, $closed->getStatus());
        $this->assertNotNull($closed->getClosedAt());
        $this->assertSame($user->getId(), $closed->getClosedBy()->getId());
    }

    public function testShouldRejectClosingTwice(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);
        $client->request('POST', $this->url($period) . '/close');

        // When
        $client->request('POST', $this->url($period) . '/close');

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldBlockRecalculationWhenClosed(): void
    {
        // Given
        [$client, $period] = $this->givenClosedPeriod();

        // When
        $client->request(
            'POST',
            $this->url($period) . '/recalculate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('zamknięty', $this->body($client)['error']);
    }

    public function testShouldBlockResetWhenClosed(): void
    {
        // Given
        [$client, $period] = $this->givenClosedPeriod();

        // When
        $client->request('POST', $this->url($period) . '/reset-adjustments');

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldBlockAdjustmentWhenClosed(): void
    {
        // Given
        [$client, $period, $entry] = $this->givenClosedPeriodWithEntry();

        // When
        $client->request(
            'PUT',
            '/bonus-settlement/entries/' . $entry->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['factorsAdjusted' => 10])
        );

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $untouched = $this->getManager()->find(BonusPeriodEntry::class, $entry->getId());
        $this->assertNull($untouched->getFactorsAdjusted());
    }

    public function testShouldReopenAndUnblockActions(): void
    {
        // Given
        [$client, $period] = $this->givenClosedPeriod();

        // When
        $client->request('POST', $this->url($period) . '/reopen');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $reopened = $this->reload($period);
        $this->assertSame(BonusPeriodStatus::OPEN, $reopened->getStatus());
        $this->assertNull($reopened->getClosedAt());
        $this->assertNull($reopened->getClosedBy());

        $client->request('POST', $this->url($period) . '/reset-adjustments');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectReopeningOpenPeriod(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);

        // When
        $client->request('POST', $this->url($period) . '/reopen');

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldLogClosingAndReopening(): void
    {
        // Given
        [$client, $period] = $this->givenClosedPeriod();

        // When
        $client->request('POST', $this->url($period) . '/reopen');

        // Then
        $this->assertCount(1, $this->logsOfType('bonus.period.closed'));
        $reopened = $this->logsOfType('bonus.period.reopened');
        $this->assertCount(1, $reopened);
        // dane zamknięcia znikają z okresu przy otwarciu, więc muszą zostać w dzienniku
        $this->assertNotNull($reopened[0]->getContentParams()['closedAt']);
        $this->assertNotNull($reopened[0]->getContentParams()['closedByLabel']);
    }

    public function testShouldRejectClosingWithoutManageGrant(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.view']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);

        // When
        $client->request('POST', $this->url($period) . '/close');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectReopeningWithoutManageGrant(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.view']);
        $client = $this->login($user);
        $period = $this->makePeriod($user, BonusPeriodStatus::CLOSED);

        // When
        $client->request('POST', $this->url($period) . '/reopen');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @return array{0: object, 1: BonusPeriod}
     */
    private function givenClosedPeriod(): array
    {
        $user = $this->createUser([], [], ['bonus-settlement.manage']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);
        $client->request('POST', $this->url($period) . '/close');

        return [$client, $period];
    }

    /**
     * @return array{0: object, 1: BonusPeriod, 2: BonusPeriodEntry}
     */
    private function givenClosedPeriodWithEntry(): array
    {
        $user = $this->createUser([], [], ['bonus-settlement.manage']);
        $client = $this->login($user);
        $period = $this->makePeriod($user);
        $entry = new BonusPeriodEntry($period, $user, 'Anna Nowak', 'dpt03', 'Szlifowanie', 62.0);
        $this->getManager()->persist($entry);
        $this->getManager()->flush();
        $client->request('POST', $this->url($period) . '/close');

        return [$client, $period, $entry];
    }

    private function makePeriod($user, ?BonusPeriodStatus $status = null): BonusPeriod
    {
        $period = new BonusPeriod(2026, 5, 5);
        if (BonusPeriodStatus::CLOSED === $status) {
            $period->close($user);
        }
        $this->getManager()->persist($period);
        $this->getManager()->flush();

        return $period;
    }

    private function url(BonusPeriod $period): string
    {
        return '/bonus-settlement/periods/' . $period->getId();
    }

    private function reload(BonusPeriod $period): BonusPeriod
    {
        $id = $period->getId();
        $this->getManager()->clear();

        return $this->getManager()->find(BonusPeriod::class, $id);
    }

    private function body($client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * @return ActivityLog[]
     */
    private function logsOfType(string $type): array
    {
        return $this->getManager()->getRepository(ActivityLog::class)->findBy(['type' => $type]);
    }
}
