<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Tests\Utilities\Factory\EntityFactory;
use Faker\Factory;

class ScheduleProductionResourcesControllerTest extends BaseScheduleReportsTestCase
{
    private EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldReturn403WhenUserHasNoRoleProduction(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEventsForVisibleDepartmentsOnly(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        $line = $this->makeLineWithProductions(
            id: 1001,
            orderNumber: 'AL-1001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-08', 'status' => '1', 'isGhost' => false, 'id' => 5001],
                ['slug' => 'dpt02', 'start' => '2026-05-10', 'end' => '2026-05-12', 'status' => '0', 'isGhost' => false, 'id' => 5002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['resources']);
        $this->assertSame('dpt01', $content['resources'][0]['id']);
        $this->assertCount(1, $content['events']);
        $this->assertSame('dpt01', $content['events'][0]['resourceId']);
        $this->assertSame('prod-5001', $content['events'][0]['id']);
        $this->assertSame('started', $content['events'][0]['orderStatus']);
        $this->assertSame('AL-1001', $content['events'][0]['orderName']);
        $this->assertSame(1001, $content['events'][0]['agreementLineId']);
    }

    public function testShouldFilterLinesByMonthRange(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        // Linia spoza okna (lipiec)
        $this->makeLineWithProductions(
            id: 2001,
            orderNumber: 'AL-2001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-07-05', 'end' => '2026-07-08', 'status' => '1', 'isGhost' => false, 'id' => 6001],
            ],
        );
        // Linia w oknie (maj)
        $this->makeLineWithProductions(
            id: 2002,
            orderNumber: 'AL-2002',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-15', 'end' => '2026-05-20', 'status' => '1', 'isGhost' => false, 'id' => 6002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['events']);
        $this->assertSame('prod-6002', $content['events'][0]['id']);
    }

    public function testShouldHideGhostByDefault(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        $this->makeLineWithProductions(
            id: 3001,
            orderNumber: 'AL-3001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-08', 'status' => '1', 'isGhost' => false, 'id' => 7001],
                ['slug' => 'dpt01', 'start' => '2026-05-20', 'end' => '2026-05-22', 'status' => '0', 'isGhost' => true, 'id' => 7002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['events']);
        $this->assertSame('prod-7001', $content['events'][0]['id']);
        $this->assertFalse($content['events'][0]['meta']['isGhost']);
    }

    public function testShouldIncludeGhostWhenRequested(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        // Linia tylko z ghostem (real prod brak)
        $this->makeLineWithProductions(
            id: 4001,
            orderNumber: 'AL-4001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-12', 'end' => '2026-05-15', 'status' => '0', 'isGhost' => true, 'id' => 8001],
            ],
            hasProduction: false,
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31&includeGhost=1');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['events']);
        $this->assertSame('prod-8001', $content['events'][0]['id']);
        $this->assertTrue($content['events'][0]['meta']['isGhost']);
        $this->assertArrayHasKey('color', $content['events'][0]);
    }

    public function testShouldRestrictByOwnedCustomersForRoleCustomer(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION', 'ROLE_CUSTOMER']);
        $client = $this->login($user);

        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $em->flush();

        $user->addCustomer($customerA);
        $em->flush();

        $this->makeLineWithProductions(
            id: 5001,
            orderNumber: 'AL-5001',
            customerId: $customerA->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-08', 'status' => '1', 'isGhost' => false, 'id' => 9001],
            ],
        );
        $this->makeLineWithProductions(
            id: 5002,
            orderNumber: 'AL-5002',
            customerId: $customerB->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-10', 'end' => '2026-05-12', 'status' => '1', 'isGhost' => false, 'id' => 9002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['events']);
        $this->assertSame('AL-5001', $content['events'][0]['orderName']);
    }

    public function testShouldReturnEmptyWhenNoVisibleDepartments(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/production-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $content['resources']);
        $this->assertSame([], $content['events']);
    }

    /**
     * @param array<int, array{slug: string, start: string, end: string, status: string, isGhost: bool, id: int}> $productions
     */
    private function makeLineWithProductions(
        int $id,
        string $orderNumber,
        int $customerId,
        array $productions,
        bool $hasProduction = true,
    ): AgreementLineRM {
        $faker = Factory::create();
        $em = $this->getManager();

        $confirmedDate = new \DateTime($productions[0]['start']);

        $rm = new AgreementLineRM($id);
        $rm->setConfirmedDate($confirmedDate);
        $rm->setStatus(AgreementLine::STATUS_MANUFACTURING);
        $rm->setIsDeleted(false);
        $rm->setIsArchived(false);
        $rm->setHasProduction($hasProduction);
        $rm->setOrderNumber($orderNumber);
        $rm->setQ($faker->text(30));
        $rm->setCustomerName($faker->name);
        $rm->setProductName($faker->word);
        $rm->setAgreementCreateDate((clone $confirmedDate)->modify('-1 week'));
        $rm->setAgreementId($faker->randomDigit());
        $rm->setCustomerId($customerId);
        $rm->setFactor(1.0);

        $prodModels = [];
        $byDpt = [];
        foreach ($productions as $p) {
            $prod = new ProductionRM(departmentSlug: $p['slug']);
            $prod->setId($p['id']);
            $prod->setDateStart(new \DateTime($p['start']));
            $prod->setDateEnd(new \DateTime($p['end']));
            $prod->setStatus($p['status']);
            $prod->setIsGhost($p['isGhost']);
            $prodModels[] = $prod;
            // Pierwsza produkcja per dpt populuje pola dpt*StartDate/EndDate (zgodnie z UpdateAgreementLineRMHandler)
            if (!isset($byDpt[$p['slug']])) {
                $byDpt[$p['slug']] = $prod;
            }
        }
        $rm->setProductions($prodModels);

        $setters = [
            'dpt01' => ['setDpt01StartDate', 'setDpt01EndDate'],
            'dpt02' => ['setDpt02StartDate', 'setDpt02EndDate'],
            'dpt03' => ['setDpt03StartDate', 'setDpt03EndDate'],
            'dpt04' => ['setDpt04StartDate', 'setDpt04EndDate'],
            'dpt05' => ['setDpt05StartDate', 'setDpt05EndDate'],
            'dpt06' => ['setDpt06StartDate', 'setDpt06EndDate'],
        ];
        foreach ($setters as $slug => [$setStart, $setEnd]) {
            if (isset($byDpt[$slug])) {
                $rm->{$setStart}($byDpt[$slug]->getDateStart());
                $rm->{$setEnd}($byDpt[$slug]->getDateEnd());
            } else {
                $rm->{$setStart}(null);
                $rm->{$setEnd}(null);
            }
        }

        $em->persist($rm);
        return $rm;
    }
}
