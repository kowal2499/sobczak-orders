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

        $line = $this->makeLineWithProductionTasks(
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
        $this->makeLineWithProductionTasks(
            id: 2001,
            orderNumber: 'AL-2001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-07-05', 'end' => '2026-07-08', 'status' => '1', 'isGhost' => false, 'id' => 6001],
            ],
        );
        // Linia w oknie (maj)
        $this->makeLineWithProductionTasks(
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

        $this->makeLineWithProductionTasks(
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
        $this->makeLineWithProductionTasks(
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

        $this->makeLineWithProductionTasks(
            id: 5001,
            orderNumber: 'AL-5001',
            customerId: $customerA->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-08', 'status' => '1', 'isGhost' => false, 'id' => 9001],
            ],
        );
        $this->makeLineWithProductionTasks(
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
}
