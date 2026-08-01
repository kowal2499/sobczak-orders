<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Definitions\TaskTypes;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;

class FactorCompletedTasksBonusTest extends BaseProductionReportsTestCase
{
    private const GRANT = 'production.factor_adjustment';
    private const REPORT_GRANT = 'reports.dashboard:on-time-bonus';

    public function testShouldReturn403WithoutGrant(): void
    {
        // Given
        $line = $this->makeCompletedLine();
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0.3,
            'comment' => 'nagroda prezesa',
        ]));

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldPersistAdjustmentAndReturnRecalculatedFactor(): void
    {
        // Given — linia ze współczynnikiem bazowym 2.0
        $line = $this->makeCompletedLine(factor: 2.0);
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0.3,
            'comment' => 'nagroda prezesa',
        ]));

        // Then
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsWithDelta(2.3, $content['factor'], 0.001);
        $this->assertNotEmpty($content['factorsStack']);

        $this->getManager()->clear();
        $factors = $this->getManager()->getRepository(Factor::class)->findBy([
            'source' => FactorSource::FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS,
        ]);
        $this->assertCount(1, $factors);
        $this->assertSame(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, $factors[0]->getDepartmentSlug());
        $this->assertEqualsWithDelta(0.3, $factors[0]->getFactorValue(), 0.001);
        $this->assertSame('nagroda prezesa', $factors[0]->getDescription());
    }

    public function testShouldAffectOnlyOnTimeReport(): void
    {
        // Given
        $line = $this->makeCompletedLine(factor: 2.0);
        $client = $this->login($this->createUser([], [], [self::GRANT, self::REPORT_GRANT]));
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0.3,
            'comment' => 'nagroda prezesa',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // When — raport "w terminie" vs stary raport ukończonych zadań
        $client->xmlHttpRequest(
            'GET',
            '/reports/production/production-tasks-on-time-summary?start=2026-05-01&end=2026-05-31'
        );
        $onTime = json_decode($client->getResponse()->getContent(), true);

        $client->xmlHttpRequest(
            'GET',
            '/reports/production/production-tasks-completion-summary?start=2026-05-01&end=2026-05-31'
        );
        $completed = json_decode($client->getResponse()->getContent(), true);

        // Then
        $this->assertEqualsWithDelta(2.3, $onTime[0]['factors']['factor'], 0.001);
        $this->assertEqualsWithDelta(2.0, $completed[0]['factors']['factor'], 0.001);
    }

    public function testShouldRejectZeroValue(): void
    {
        // Given
        $line = $this->makeCompletedLine();
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0,
            'comment' => 'nic',
        ]));

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectUnknownDepartment(): void
    {
        // Given
        $line = $this->makeCompletedLine();
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => 'dpt99',
            'value' => 0.3,
            'comment' => 'nagroda prezesa',
        ]));

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldRejectEmptyComment(): void
    {
        // Given
        $line = $this->makeCompletedLine();
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0.3,
            'comment' => '   ',
        ]));

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldSurviveFullFactorsReplacement(): void
    {
        // Given — korekta z raportu + pełna podmiana współczynników z ekranu linii
        $line = $this->makeCompletedLine(factor: 2.0);
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $client->xmlHttpRequest('POST', $this->url($line->getId()), [], [], [], json_encode([
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'value' => 0.3,
            'comment' => 'nagroda prezesa',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // When — stary endpoint podmienia zestaw ratio/bonus (bez korekt z raportu w payloadzie)
        $client->request('POST', '/production/factor/' . $line->getId(), [
            'factors' => [
                ['source' => 'agreement_line', 'value' => 2.0],
            ],
        ]);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Then — korekta nadal w bazie
        $this->getManager()->clear();
        $factors = $this->getManager()->getRepository(Factor::class)->findBy([
            'source' => FactorSource::FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS,
        ]);
        $this->assertCount(1, $factors);
    }

    private function url(int $agreementLineId): string
    {
        return "/production/factor/{$agreementLineId}/completed-tasks-bonus";
    }

    private function makeCompletedLine(float $factor = 1.0): \App\Entity\AgreementLine
    {
        return $this->makeAgreementLine(factor: $factor, productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-05-10'),
            'dateEnd' => new \DateTime('2026-05-20'),
            'completedAt' => new \DateTime('2026-05-15 12:00:00'),
        ]]);
    }
}
