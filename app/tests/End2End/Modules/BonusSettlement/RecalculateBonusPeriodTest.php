<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\Entity\Definitions\TaskTypes;
use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Service\AuthCacheService;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use App\Tests\End2End\Modules\Reports\Production\BaseProductionReportsTestCase;

/**
 * Przeliczenie okresu i zerowanie korekt.
 *
 * Dziedziczy po bazie testów raportów, bo wsad premiowy pochodzi z tego samego miernika -
 * potrzebne są realne AgreementLine + Production z przebudowanym read modelem.
 */
class RecalculateBonusPeriodTest extends BaseProductionReportsTestCase
{
    private const GRINDING_GRANT = 'production.show.grinding';
    private const CNC_GRANT = 'production.show.cnc';

    public function testShouldCreateEntryPerUserAndDepartment(): void
    {
        // Given
        $user = $this->createUser([], [], [
            'bonus-settlement.manage',
            self::GRINDING_GRANT,
            self::CNC_GRANT,
        ]);
        $client = $this->login($user);
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();

        // When
        $this->recalculate($client, $period);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $entries = $this->entriesOf($period);
        $this->assertCount(2, $entries, 'dwa granty działowe to dwa wiersze');

        $grinding = $this->entryFor($entries, TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $this->assertGreaterThan(0.0, $grinding->getFactorsCalculated());
        $this->assertSame($user->getId(), $grinding->getUser()->getId());
        $this->assertSame('Szlifowanie', $grinding->getDepartmentLabel());

        $cnc = $this->entryFor($entries, TaskTypes::TYPE_DEFAULT_SLUG_CNC);
        $this->assertSame(0.0, $cnc->getFactorsCalculated(), 'dział bez produkcji dostaje zero');
    }

    public function testShouldSkipUserWithoutDepartmentGrant(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();

        // When
        $this->recalculate($client, $period);

        // Then
        $this->assertSame([], $this->entriesOf($period));
    }

    public function testShouldRefreshInputAndKeepAdjustment(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();
        $this->recalculate($client, $period);

        $entry = $this->entryFor($this->entriesOf($period), TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $firstInput = $entry->getFactorsCalculated();
        $entry->adjust(1.5, 'spóźniony powrót z urlopu');
        $this->getManager()->flush();

        // When - dokładamy drugą ukończoną produkcję i liczymy jeszcze raz
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $this->recalculate($client, $period);

        // Then
        $entries = $this->entriesOf($period);
        $this->assertCount(1, $entries, 'przeliczenie nie duplikuje wierszy');
        $refreshed = $entries[0];
        $this->assertGreaterThan($firstInput, $refreshed->getFactorsCalculated());
        $this->assertSame(1.5, $refreshed->getFactorsAdjusted());
        $this->assertSame('spóźniony powrót z urlopu', $refreshed->getNote());
        $this->assertSame(1.5, $refreshed->getEffectiveFactors());
    }

    public function testShouldFlagAdjustmentAfterInputChanged(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();
        $this->recalculate($client, $period);
        $entry = $this->entriesOf($period)[0];
        $entry->adjust(1.5, null);
        $this->getManager()->flush();
        $this->assertFalse($entry->isAdjustmentStale());

        // When
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $this->recalculate($client, $period);

        // Then
        $this->assertTrue($this->entriesOf($period)[0]->isAdjustmentStale());
    }

    public function testShouldNotFlagAdjustmentWhenInputStayedTheSame(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();
        $this->recalculate($client, $period);
        $entry = $this->entriesOf($period)[0];
        $entry->adjust(1.5, null);
        $this->getManager()->flush();

        // When - przeliczenie bez zmiany danych źródłowych
        $this->recalculate($client, $period);

        // Then
        $this->assertFalse($this->entriesOf($period)[0]->isAdjustmentStale());
    }

    public function testShouldRemoveEntryWhenUserLostDepartmentGrant(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]);
        $client = $this->login($user);
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();
        $this->recalculate($client, $period);
        $this->assertCount(1, $this->entriesOf($period));

        // When
        $this->revokeGrant($user, self::GRINDING_GRANT);
        $this->recalculate($client, $period);

        // Then
        $this->assertSame([], $this->entriesOf($period));
    }

    public function testShouldStampToleranceAndCalculationTime(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $period = $this->makePeriod();
        $this->assertNull($period->getCalculatedAt());

        // When
        $this->recalculate($client, $period, 2);

        // Then
        $this->getManager()->refresh($period);
        $this->assertSame(2, $period->getToleranceDays());
        $this->assertNotNull($period->getCalculatedAt());
    }

    public function testShouldClearAllAdjustments(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $this->completedProduction(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $period = $this->makePeriod();
        $this->recalculate($client, $period);
        $entry = $this->entriesOf($period)[0];
        $entry->adjust(1.5, 'do skasowania');
        $this->getManager()->flush();

        // When
        $client->request('POST', $this->periodUrl($period) . '/reset-adjustments');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $cleared = $this->entriesOf($period)[0];
        $this->assertNull($cleared->getFactorsAdjusted());
        $this->assertNull($cleared->getNote());
        $this->assertNull($cleared->getAdjustedAt());
        $this->assertNull($cleared->getAdjustedAgainst());
    }

    public function testShouldLogBothActions(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage', self::GRINDING_GRANT]));
        $period = $this->makePeriod();

        // When
        $this->recalculate($client, $period);
        $client->request('POST', $this->periodUrl($period) . '/reset-adjustments');

        // Then
        $this->assertCount(1, $this->logsOfType('bonus.period.recalculated'));
        $this->assertCount(1, $this->logsOfType('bonus.period.adjustments_reset'));
    }

    public function testShouldRejectRecalculationWithoutManageGrant(): void
    {
        // Given
        $manager = $this->createUser([], [], ['bonus-settlement.manage']);
        $period = $this->makePeriod();
        $client = $this->login($this->createUser([], [], ['bonus-settlement.view']));

        // When
        $this->recalculate($client, $period);

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectUnknownPeriod(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['bonus-settlement.manage']));

        // When
        $client->request(
            'POST',
            '/bonus-settlement/periods/999999/recalculate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    private function makePeriod(int $year = 2026, int $month = 5): BonusPeriod
    {
        $period = new BonusPeriod($year, $month, 5);
        $this->getManager()->persist($period);
        $this->getManager()->flush();

        return $period;
    }

    private function periodUrl(BonusPeriod $period): string
    {
        return '/bonus-settlement/periods/' . $period->getId();
    }

    private function recalculate($client, BonusPeriod $period, ?int $toleranceDays = null): void
    {
        $client->request(
            'POST',
            $this->periodUrl($period) . '/recalculate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(null === $toleranceDays ? [] : ['toleranceDays' => $toleranceDays])
        );
    }

    /**
     * Produkcja ukończona w oknie i w maju 2026 - kwalifikuje się do premii za ten okres.
     */
    private function completedProduction(string $slug): void
    {
        $this->makeAgreementLine(factor: 2.0, productions: [[
            'slug' => $slug,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-05-10'),
            'dateEnd' => new \DateTime('2026-05-20'),
            'completedAt' => new \DateTime('2026-05-15 12:00:00'),
        ]]);
    }

    /**
     * @return BonusPeriodEntry[]
     */
    private function entriesOf(BonusPeriod $period): array
    {
        $this->getManager()->clear();
        $period = $this->getManager()->find(BonusPeriod::class, $period->getId());

        return $this->getManager()
            ->getRepository(BonusPeriodEntry::class)
            ->findBy(['period' => $period], ['departmentSlug' => 'ASC']);
    }

    /**
     * @param BonusPeriodEntry[] $entries
     */
    private function entryFor(array $entries, string $slug): BonusPeriodEntry
    {
        foreach ($entries as $entry) {
            if ($entry->getDepartmentSlug() === $slug) {
                return $entry;
            }
        }

        $this->fail(sprintf('Brak wiersza dla działu %s', $slug));
    }

    private function revokeGrant(User $user, string $grantSlug): void
    {
        $values = $this->getManager()->getRepository(AuthUserGrantValue::class)->findBy(['user' => $user]);
        foreach ($values as $value) {
            if ($value->getGrantVO()->toString() === $grantSlug) {
                $this->getManager()->remove($value);
            }
        }
        $this->getManager()->flush();
        $this->get(AuthCacheService::class)->invalidateAll();
    }

    /**
     * @return ActivityLog[]
     */
    private function logsOfType(string $type): array
    {
        return $this->getManager()->getRepository(ActivityLog::class)->findBy(['type' => $type]);
    }
}
