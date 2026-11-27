<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\Customer;
use App\Tests\Utilities\Factory\EntityFactory;

class ScheduleDepartmentLoadControllerTest extends BaseScheduleReportsTestCase
{
    private const URL = '/reports/schedule/department-load?startDate=2026-05-01&endDate=2026-05-31';

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
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL);

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldCountActiveTasksPerDepartmentAndDay(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']));
        $customerId = $this->makeCustomerId();
        $this->makeLineWithProductionTasks(6001, 'AL-6001', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-07', 'status' => '0', 'isGhost' => false, 'id' => 7001],
        ]);
        $this->makeLineWithProductionTasks(6002, 'AL-6002', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-06', 'end' => '2026-05-08', 'status' => '2', 'isGhost' => false, 'id' => 7002],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL);

        // Then
        $this->assertSame(
            ['2026-05-05' => 1, '2026-05-06' => 2, '2026-05-07' => 2, '2026-05-08' => 1],
            $content['load']['dpt01']
        );
    }

    public function testShouldSkipCompletedNotApplicableAndGhostTasks(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']));
        $customerId = $this->makeCustomerId();
        $this->makeLineWithProductionTasks(6011, 'AL-6011', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-07', 'status' => '3', 'isGhost' => false, 'id' => 7011],
        ]);
        $this->makeLineWithProductionTasks(6012, 'AL-6012', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-07', 'status' => '4', 'isGhost' => false, 'id' => 7012],
        ]);
        $this->makeLineWithProductionTasks(6013, 'AL-6013', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-07', 'status' => '0', 'isGhost' => true, 'id' => 7013],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL);

        // Then
        $this->assertArrayNotHasKey('dpt01', $content['load']);
    }

    public function testShouldExcludeGivenAgreementLine(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']));
        $customerId = $this->makeCustomerId();
        $this->makeLineWithProductionTasks(6021, 'AL-6021', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-05', 'status' => '0', 'isGhost' => false, 'id' => 7021],
        ]);
        $this->makeLineWithProductionTasks(6022, 'AL-6022', $customerId, [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-06', 'status' => '0', 'isGhost' => false, 'id' => 7022],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL . '&excludeAgreementLineId=6022');

        // Then
        $this->assertSame(['2026-05-05' => 1], $content['load']['dpt01']);
    }

    public function testShouldReturnAllDepartmentsButLoadOnlyForGranted(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']));
        $this->makeLineWithProductionTasks(6031, 'AL-6031', $this->makeCustomerId(), [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-05', 'status' => '0', 'isGhost' => false, 'id' => 7031],
            ['slug' => 'dpt02', 'start' => '2026-05-06', 'end' => '2026-05-06', 'status' => '0', 'isGhost' => false, 'id' => 7032],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL);

        // Then
        $this->assertCount(6, $content['departments']);
        $access = array_column($content['departments'], 'hasAccess', 'id');
        $this->assertSame(['dpt01'], array_keys(array_filter($access)));
        $this->assertSame(['dpt01'], array_keys($content['load']));
    }

    public function testShouldNotFilterLoadByCustomerOwnership(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION', 'ROLE_CUSTOMER']);
        $client = $this->login($user);
        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $em->flush();
        $user->addCustomer($customerA);
        $this->makeLineWithProductionTasks(6041, 'AL-6041', $customerA->getId(), [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-05', 'status' => '0', 'isGhost' => false, 'id' => 7041],
        ]);
        $this->makeLineWithProductionTasks(6042, 'AL-6042', $customerB->getId(), [
            ['slug' => 'dpt01', 'start' => '2026-05-05', 'end' => '2026-05-05', 'status' => '0', 'isGhost' => false, 'id' => 7042],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL);

        // Then
        $this->assertSame(['2026-05-05' => 2], $content['load']['dpt01']);
    }

    public function testShouldClipToRequestedRange(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['production.show.gluing'], ['ROLE_PRODUCTION']));
        $this->makeLineWithProductionTasks(6051, 'AL-6051', $this->makeCustomerId(), [
            ['slug' => 'dpt01', 'start' => '2026-04-28', 'end' => '2026-05-03', 'status' => '0', 'isGhost' => false, 'id' => 7051],
        ]);
        $this->flushAndClear();

        // When
        $content = $this->fetch($client, self::URL);

        // Then
        $this->assertSame(['2026-05-01' => 1, '2026-05-02' => 1, '2026-05-03' => 1], $content['load']['dpt01']);
    }

    private function makeCustomerId(): int
    {
        $customer = $this->factory->make(Customer::class);
        $this->getManager()->flush();

        return $customer->getId();
    }

    private function flushAndClear(): void
    {
        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch($client, string $url): array
    {
        $client->xmlHttpRequest('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        return json_decode($client->getResponse()->getContent(), true);
    }
}
