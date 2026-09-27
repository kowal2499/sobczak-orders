<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;

/**
 * Charakteryzuje obecne zachowanie GET /reports/production/production-capacity (wykres Capacity).
 *
 * Reguły utrwalone z DoctrineProductionTasksRepository::getCapacityInTime():
 *  - tylko działy domyślne (dpt01–dpt06),
 *  - filtr po p.dateEnd BETWEEN start(00:00) AND end(23:59:59),
 *  - isGhost=0 domyślnie; includeGhost=1 włącza zadania ghost,
 *  - jeden rekord (ProductionReportRecordDTO) na produkcję, factory FACTOR_ADJUSTMENT_RATIO.
 */
class ProductionCapacityTest extends BaseProductionReportsTestCase
{
    private const URL = '/reports/production/production-capacity';
    private const GRANT = 'reports.dashboard:capacity-utilization';

    public function testShouldReturnEmptyArrayWhenNoData(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldReturnRecordForProductionEndingInRange(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(
            factor: 2.0,
            productions: [
                ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC, 'dateStart' => new \DateTime('2026-05-10'), 'dateEnd' => new \DateTime('2026-05-15')],
            ],
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertSame(TaskTypes::TYPE_DEFAULT_SLUG_CNC, $content[0]['departmentSlug']);
        $this->assertFalse($content[0]['isGhost']);
        $this->assertArrayHasKey('factors', $content[0]);
        $this->assertArrayHasKey('factorsStack', $content[0]['factors']);
    }

    public function testShouldExcludeProductionEndingOutsideRange(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [
            ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC, 'dateStart' => new \DateTime('2026-06-10'), 'dateEnd' => new \DateTime('2026-06-15')],
        ]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldHideGhostByDefault(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [
            ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC, 'dateStart' => new \DateTime('2026-05-05'), 'dateEnd' => new \DateTime('2026-05-08'), 'isGhost' => false],
            ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, 'dateStart' => new \DateTime('2026-05-20'), 'dateEnd' => new \DateTime('2026-05-22'), 'isGhost' => true],
        ]);

        // When (default - bez ghost)
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertFalse($content[0]['isGhost']);
    }

    public function testShouldIncludeGhostWhenRequested(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [
            ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC, 'dateStart' => new \DateTime('2026-05-05'), 'dateEnd' => new \DateTime('2026-05-08'), 'isGhost' => false],
            ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, 'dateStart' => new \DateTime('2026-05-20'), 'dateEnd' => new \DateTime('2026-05-22'), 'isGhost' => true],
        ]);

        // When (z ghost)
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31&includeGhost=1');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $content);
    }

    public function testShouldExcludeNonDefaultDepartments(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [self::GRANT]));
        $this->makeAgreementLine(productions: [
            ['slug' => 'custom-department', 'dateStart' => new \DateTime('2026-05-10'), 'dateEnd' => new \DateTime('2026-05-15')],
        ]);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShouldMaskForeignLinesButKeepThemInTotalsForRoleCustomer(): void
    {
        // Given
        $ownCustomer = $this->factory->make(Customer::class);
        $foreignCustomer = $this->factory->make(Customer::class);
        $user = $this->createUser([], [], [self::GRANT], ['ROLE_CUSTOMER']);
        $user->addCustomer($ownCustomer);
        $production = [[
            'slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC,
            'dateStart' => new \DateTime('2026-05-10'),
            'dateEnd' => new \DateTime('2026-05-15'),
        ]];
        $ownLine = $this->makeAgreementLine(customer: $ownCustomer, factor: 2.0, productions: $production);
        $this->makeAgreementLine(customer: $foreignCustomer, factor: 3.0, productions: $production);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $content);
        $this->assertEqualsWithDelta(5.0, array_sum(array_column(array_column($content, 'factors'), 'factor')), 0.001);

        $visible = array_values(array_filter($content, fn (array $r) => $r['agreementLine'] !== null));
        $this->assertCount(1, $visible);
        $this->assertSame($ownLine->getId(), $visible[0]['agreementLine']['id']);

        $masked = array_values(array_filter($content, fn (array $r) => $r['agreementLine'] === null));
        $this->assertNull($masked[0]['agreement']);
        $this->assertNull($masked[0]['customer']);
        $this->assertSame([], $masked[0]['factors']['factorsStack']);
    }

    public function testShouldDenyAccessWithoutGrant(): void
    {
        // Given
        $client = $this->login($this->createUser(legacyRoles: ['ROLE_PRODUCTION']));

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldAllowAdminForDifferencesReport(): void
    {
        // Given
        $client = $this->login($this->createUser(legacyRoles: ['ROLE_ADMIN']));

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
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
