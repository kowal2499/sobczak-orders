<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Definitions\TaskTypes;

/**
 * Charakteryzuje obecne zachowanie GET /reports/production/production-tasks-completion-summary
 * (miernik "Departments Bonus").
 *
 * Reguły utrwalone z DoctrineProductionTasksRepository::getProductions():
 *  - tylko działy domyślne (dpt01–dpt06),
 *  - isCompleted=1 AND completedAt IS NOT NULL AND isGhost=0,
 *  - completedAt BETWEEN start(00:00) AND end(23:59:59),
 *  - jeden rekord (ProductionReportRecordDTO) na produkcję, factory FACTOR_ADJUSTMENT_BONUS.
 */
class ProductionTasksCompletionSummaryTest extends BaseProductionReportsTestCase
{
    private const URL = '/reports/production/production-tasks-completion-summary';

    public function testShouldReturnEmptyArrayWhenNoData(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldReturnRecordForCompletedProductionInRange(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(
            factor: 3.0,
            productions: [[
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
                'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                'isCompleted' => true,
                'completedAt' => new \DateTime('2026-05-15'),
            ]],
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertSame(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, $content[0]['departmentSlug']);
        $this->assertArrayHasKey('factors', $content[0]);
        $this->assertArrayHasKey('factorsStack', $content[0]['factors']);
    }

    public function testShouldExcludeNotCompletedProduction(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => false,
            'completedAt' => new \DateTime('2026-05-15'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldExcludeProductionCompletedOutsideRange(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'completedAt' => new \DateTime('2026-06-15'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldExcludeGhostProduction(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'completedAt' => new \DateTime('2026-05-15'),
            'isGhost' => true,
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldExcludeNonDefaultDepartments(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [[
            'slug' => 'custom-department',
            'isCompleted' => true,
            'completedAt' => new \DateTime('2026-05-15'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldIncludeOtherDepartmentsOfReportedLineAsOutOfRange(): void
    {
        // Given — dpt03 ukończony w maju, dpt05 dopiero w czerwcu
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
                'isCompleted' => true,
                'completedAt' => new \DateTime('2026-05-15'),
            ],
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING,
                'isCompleted' => true,
                'dateStart' => new \DateTime('2026-06-04'),
                'dateEnd' => new \DateTime('2026-06-11'),
                'completedAt' => new \DateTime('2026-06-08'),
            ],
        ]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $content);

        $bySlug = array_column($content, null, 'departmentSlug');

        $inRange = $bySlug[TaskTypes::TYPE_DEFAULT_SLUG_GRINDING];
        $this->assertTrue($inRange['inRange']);
        $this->assertNotNull($inRange['factors']);

        // rekord bez współczynnika — dymek komórki odróżnia dzięki niemu "dział pracował
        // w innym miesiącu" od "linia nie przechodzi przez ten dział"
        $outOfRange = $bySlug[TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING];
        $this->assertFalse($outOfRange['inRange']);
        $this->assertNull($outOfRange['factors']);
        $this->assertStringStartsWith('2026-06-04', $outOfRange['dateStart']);
        $this->assertStringStartsWith('2026-06-11', $outOfRange['dateEnd']);
        $this->assertStringStartsWith('2026-06-08', $outOfRange['completedAt']);
    }

    public function testShouldNotEmitOutOfRangeRecordsForLineWithoutAnyQualifyingProduction(): void
    {
        // Given — cała linia poza zakresem raportu
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(productions: [
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
                'isCompleted' => true,
                'completedAt' => new \DateTime('2026-06-15'),
            ],
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING,
                'isCompleted' => false,
            ],
        ]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldNotCountOutOfRangeRecordsInDepartmentTotals(): void
    {
        // Given — ta sama linia rozliczona w maju i (poza zakresem) w czerwcu
        $client = $this->login($this->createUser());
        $this->makeAgreementLine(factor: 3.0, productions: [
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
                'isCompleted' => true,
                'completedAt' => new \DateTime('2026-05-15'),
            ],
            [
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING,
                'isCompleted' => true,
                'completedAt' => new \DateTime('2026-06-08'),
            ],
        ]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then — suma liczona jak na froncie: rekordy bez zakresu nie wnoszą współczynnika
        $content = json_decode($client->getResponse()->getContent(), true);
        $total = array_sum(array_map(
            fn (array $record) => false === $record['inRange'] ? 0.0 : $record['factors']['factor'],
            $content
        ));

        $this->assertSame(3.0, $total);
    }

    public function testShouldReturn400WhenDatesMissing(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL);

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }
}
