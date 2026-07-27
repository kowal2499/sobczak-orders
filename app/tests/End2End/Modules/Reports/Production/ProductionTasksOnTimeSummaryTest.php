<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Definitions\TaskTypes;

/**
 * GET /reports/production/production-tasks-on-time-summary (miernik "Departments Bonus — w terminie").
 *
 * Jak "departments_bonus" (ukończone działy domyślne, completedAt w zakresie miesiąca), ale każdy
 * rekord niesie flagę `onTime`: true tylko gdy completedAt mieści się w oknie [dateStart, dateEnd].
 * Rekordy poza oknem NIE są odfiltrowywane — są zwracane z onTime=false (front pokazuje 0/wyszarzone).
 */
class ProductionTasksOnTimeSummaryTest extends BaseProductionReportsTestCase
{
    private const URL = '/reports/production/production-tasks-on-time-summary';
    private const GRANT = 'reports.dashboard:on-time-bonus';

    public function testShouldReturn403WithoutGrant(): void
    {
        // Given — użytkownik bez dedykowanego grantu
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldMarkOnTimeWhenCompletedInsideWindow(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-05-10'),
            'dateEnd' => new \DateTime('2026-05-20'),
            'completedAt' => new \DateTime('2026-05-15 12:00:00'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertSame(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, $content[0]['departmentSlug']);
        $this->assertTrue($content[0]['onTime']);
    }

    public function testShouldIncludeButMarkOffTimeWhenCompletedAfterWindow(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-05-10'),
            'dateEnd' => new \DateTime('2026-05-20'),
            'completedAt' => new \DateTime('2026-05-25 12:00:00'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertFalse($content[0]['onTime']);
    }

    public function testShouldMarkOffTimeWhenCompletedBeforeWindow(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-05-15'),
            'dateEnd' => new \DateTime('2026-05-25'),
            'completedAt' => new \DateTime('2026-05-05 12:00:00'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertFalse($content[0]['onTime']);
    }

    public function testShouldExcludeProductionCompletedOutsideMonth(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
            'isCompleted' => true,
            'dateStart' => new \DateTime('2026-06-10'),
            'dateEnd' => new \DateTime('2026-06-20'),
            'completedAt' => new \DateTime('2026-06-15 12:00:00'),
        ]]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldReturn400WhenDatesMissing(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('GET', self::URL);

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }
}
