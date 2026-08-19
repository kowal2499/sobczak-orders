<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\System\Test\ApiTestCase;

/**
 * PUT /bonus-settlement/entries/{id} - ręczna korekta jednego wiersza rozliczenia.
 *
 * Wiersze zakładamy wprost, bez przeliczania: przedmiotem testu jest sama korekta, a nie
 * pochodzenie wsadu (to pokrywa RecalculateBonusPeriodTest).
 */
class AdjustBonusEntryTest extends ApiTestCase
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

    public function testShouldSaveAdjustmentWithNote(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => 40.0, 'note' => 'dwa dni nieobecności']);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $saved = $this->reload($entry);
        $this->assertSame(40.0, $saved->getFactorsAdjusted());
        $this->assertSame('dwa dni nieobecności', $saved->getNote());
        $this->assertSame(40.0, $saved->getEffectiveFactors());
        $this->assertNotNull($saved->getAdjustedAt());
        $this->assertSame(62.0, $saved->getAdjustedAgainst());
    }

    /**
     * Zero to decyzja o odebraniu premii, nie brak korekty - stąd osobny test na pułapkę ??.
     */
    public function testShouldSaveZeroAsBindingValue(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => 0, 'note' => 'cały miesiąc na zwolnieniu']);

        // Then
        $saved = $this->reload($entry);
        $this->assertSame(0.0, $saved->getFactorsAdjusted());
        $this->assertSame(0.0, $saved->getEffectiveFactors());
        $this->assertTrue($saved->isAdjusted());
    }

    public function testShouldClearAdjustmentOnNullValue(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);
        $this->put($client, $entry, ['factorsAdjusted' => 40.0, 'note' => 'pomyłka']);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => null]);

        // Then
        $cleared = $this->reload($entry);
        $this->assertNull($cleared->getFactorsAdjusted());
        $this->assertNull($cleared->getNote());
        $this->assertNull($cleared->getAdjustedAt());
        $this->assertNull($cleared->getAdjustedAgainst());
        $this->assertSame(62.0, $cleared->getEffectiveFactors());
    }

    public function testShouldRejectNegativeAdjustment(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => -5]);

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->assertNull($this->reload($entry)->getFactorsAdjusted());
    }

    public function testShouldRejectNonNumericValue(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => 'dużo']);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectUnknownEntry(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));

        // When
        $client->request(
            'PUT',
            '/bonus-settlement/entries/999999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['factorsAdjusted' => 10])
        );

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectAdjustmentWithoutManageGrant(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0, ['bonus-settlement.view']);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => 40.0]);

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldLogValueBeforeAndAfter(): void
    {
        // Given
        [$client, $entry] = $this->givenEntry(62.0);

        // When
        $this->put($client, $entry, ['factorsAdjusted' => 40.0, 'note' => 'spóźnienia']);

        // Then
        $logs = $this->getManager()->getRepository(ActivityLog::class)->findBy(['type' => 'bonus.entry.adjusted']);
        $this->assertCount(1, $logs);
        $params = $logs[0]->getContentParams();
        $this->assertSame(62.0, $params['valueBefore']);
        $this->assertSame(40.0, $params['valueAfter']);
        $this->assertSame('spóźnienia', $params['note']);
        $this->assertSame('Szlifowanie', $params['departmentLabel']);
    }

    /**
     * @return array{0: object, 1: BonusPeriodEntry}
     */
    private function givenEntry(float $calculated, array $grants = ['bonus-settlement.manage']): array
    {
        $user = $this->createUser([], [], $grants);
        $client = $this->login($user);

        $period = new BonusPeriod(2026, 5, 5);
        $this->getManager()->persist($period);
        $entry = new BonusPeriodEntry($period, $user, 'Anna Nowak', 'dpt03', 'Szlifowanie', $calculated);
        $this->getManager()->persist($entry);
        $this->getManager()->flush();

        return [$client, $entry];
    }

    private function put($client, BonusPeriodEntry $entry, array $payload): void
    {
        $client->request(
            'PUT',
            '/bonus-settlement/entries/' . $entry->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
    }

    private function reload(BonusPeriodEntry $entry): BonusPeriodEntry
    {
        $id = $entry->getId();
        $this->getManager()->clear();

        return $this->getManager()->find(BonusPeriodEntry::class, $id);
    }
}
