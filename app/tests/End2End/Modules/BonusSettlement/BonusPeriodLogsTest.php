<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\System\Test\ApiTestCase;

/**
 * Dziennik okresu wystawiany pod widok rozliczenia.
 */
class BonusPeriodLogsTest extends ApiTestCase
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

    public function testShouldReturnLogsOfThePeriodNewestFirst(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']);
        $client = $this->login($user);
        $period = $this->makePeriod();
        $entry = $this->makeEntry($period, $user);

        $client->request(
            'PUT',
            '/bonus-settlement/entries/' . $entry->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['factorsAdjusted' => 40, 'note' => 'ustalenie z kierownikiem'])
        );
        $client->request('POST', '/bonus-settlement/periods/' . $period->getId() . '/close');

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $period->getId() . '/logs');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, $body['total']);
        $this->assertSame('bonus.period.closed', $body['items'][0]['type']);
        $this->assertSame('bonus.entry.adjusted', $body['items'][1]['type']);
    }

    public function testShouldTranslateContentWithParams(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']);
        $client = $this->login($user);
        $period = $this->makePeriod();
        $entry = $this->makeEntry($period, $user);

        $client->request(
            'PUT',
            '/bonus-settlement/entries/' . $entry->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['factorsAdjusted' => 40, 'note' => 'ustalenie z kierownikiem'])
        );

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $period->getId() . '/logs');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true)['items'][0]['content'];
        $this->assertStringContainsString('Anna Nowak', $content);
        $this->assertStringContainsString('Szlifowanie', $content);
        $this->assertStringContainsString('ustalenie z kierownikiem', $content);
        // klucz tłumaczenia nie może przeciec do interfejsu
        $this->assertStringNotContainsString('activity_log.', $content);
    }

    public function testShouldOmitEmptyNoteFromContent(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']);
        $client = $this->login($user);
        $period = $this->makePeriod();
        $entry = $this->makeEntry($period, $user);

        $client->request(
            'PUT',
            '/bonus-settlement/entries/' . $entry->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['factorsAdjusted' => 40])
        );

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $period->getId() . '/logs');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true)['items'][0]['content'];
        $this->assertStringNotContainsString('null', $content);
        $this->assertStringNotContainsString('()', $content);
    }

    public function testShouldOpenTheJournalWithPeriodCreation(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']);
        $client = $this->login($user);

        // When
        $client->request(
            'POST',
            '/bonus-settlement/periods',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['year' => 2026, 'month' => 7])
        );

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $period = $this->getManager()->getRepository(BonusPeriod::class)
            ->findOneBy(['year' => 2026, 'month' => 7]);

        $client->request('GET', '/bonus-settlement/periods/' . $period->getId() . '/logs');
        $body = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $body['total']);
        $this->assertSame('bonus.period.created', $body['items'][0]['type']);
        $this->assertStringContainsString('2026-07', $body['items'][0]['content']);
        $this->assertSame($user->getId(), $body['items'][0]['user']['id']);
    }

    public function testShouldNotLeakLogsOfAnotherPeriod(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', 'bonus-settlement.view']);
        $client = $this->login($user);
        $closed = $this->makePeriod(2026, 5);
        $other = $this->makePeriod(2026, 6);
        $client->request('POST', '/bonus-settlement/periods/' . $closed->getId() . '/close');

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $other->getId() . '/logs');

        // Then
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $body['total']);
    }

    public function testShouldRejectUserWithoutViewGrant(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);
        $period = $this->makePeriod();

        // When
        $client->request('GET', '/bonus-settlement/periods/' . $period->getId() . '/logs');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    private function makePeriod(int $year = 2026, int $month = 5): BonusPeriod
    {
        $period = new BonusPeriod($year, $month, 5);
        $this->getManager()->persist($period);
        $this->getManager()->flush();

        return $period;
    }

    private function makeEntry(BonusPeriod $period, $user): BonusPeriodEntry
    {
        $entry = new BonusPeriodEntry($period, $user, 'Anna Nowak', 'dpt03', 'Szlifowanie', 62.0);
        $this->getManager()->persist($entry);
        $this->getManager()->flush();

        return $entry;
    }
}
