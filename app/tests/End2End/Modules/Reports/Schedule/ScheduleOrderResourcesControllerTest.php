<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Tests\Utilities\Factory\EntityFactory;
use Faker\Factory;

class ScheduleOrderResourcesControllerTest extends BaseScheduleReportsTestCase
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

    public function testShouldReturn403WhenUserHasNoCalendarOrdersGrant(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/order-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturnOrderWithProductionsForVisibleDepartmentsOnly(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['reports.calendar_orders', 'production.show.gluing'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        // confirmedDate = 2026-05-08, agreementCreateDate = 2026-05-01 (overlaps May window)
        $this->makeLineWithProductions(
            id: 1001,
            orderNumber: 'AL-1001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-08', 'end' => '2026-05-12', 'status' => '1', 'isGhost' => false, 'id' => 5001],
                ['slug' => 'dpt02', 'start' => '2026-05-13', 'end' => '2026-05-15', 'status' => '0', 'isGhost' => false, 'id' => 5002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/order-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['orders']);

        $order = $content['orders'][0];
        $this->assertSame(1001, $order['id']);
        $this->assertSame('AL-1001', $order['orderNumber']);
        $this->assertSame('2026-05-08', $order['dateEnd']);
        $this->assertSame('2026-05-01', $order['dateStart']);

        // dpt02 is not granted -> only dpt01 production is returned
        $this->assertCount(1, $order['productions']);
        $this->assertSame(5001, $order['productions'][0]['id']);
        $this->assertSame('dpt01', $order['productions'][0]['departmentSlug']);
        $this->assertSame('started', $order['productions'][0]['status']);
        $this->assertSame('2026-05-08', $order['productions'][0]['dateStart']);
    }

    public function testShouldReturnOrderWithoutProductionsWhenNoDepartmentVisible(): void
    {
        // Given — user can see the calendar but has no production department grants
        $em = $this->getManager();
        $user = $this->createUser([], [], ['reports.calendar_orders']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        $this->makeLineWithProductions(
            id: 2001,
            orderNumber: 'AL-2001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-08', 'end' => '2026-05-12', 'status' => '1', 'isGhost' => false, 'id' => 6001],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/order-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then — order is visible, but its productions are empty (not production-gated)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['orders']);
        $this->assertSame('AL-2001', $content['orders'][0]['orderNumber']);
        $this->assertSame([], $content['orders'][0]['productions']);
    }

    public function testShouldFilterOrdersByRangeOverlap(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['reports.calendar_orders']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        // Out of window: confirmedDate 2026-03-10, createDate 2026-03-03 — both before May
        $this->makeLineWithProductions(
            id: 3001,
            orderNumber: 'AL-3001',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-03-10', 'end' => '2026-03-12', 'status' => '1', 'isGhost' => false, 'id' => 7001],
            ],
        );
        // In window: confirmedDate 2026-05-20
        $this->makeLineWithProductions(
            id: 3002,
            orderNumber: 'AL-3002',
            customerId: $customer->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-20', 'end' => '2026-05-22', 'status' => '1', 'isGhost' => false, 'id' => 7002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/order-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['orders']);
        $this->assertSame('AL-3002', $content['orders'][0]['orderNumber']);
    }

    public function testShouldRestrictByOwnedCustomersForRoleCustomer(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['reports.calendar_orders'], ['ROLE_CUSTOMER']);
        $client = $this->login($user);

        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $em->flush();

        $user->addCustomer($customerA);
        $em->flush();

        $this->makeLineWithProductions(
            id: 4001,
            orderNumber: 'AL-4001',
            customerId: $customerA->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-08', 'end' => '2026-05-10', 'status' => '1', 'isGhost' => false, 'id' => 8001],
            ],
        );
        $this->makeLineWithProductions(
            id: 4002,
            orderNumber: 'AL-4002',
            customerId: $customerB->getId(),
            productions: [
                ['slug' => 'dpt01', 'start' => '2026-05-12', 'end' => '2026-05-14', 'status' => '1', 'isGhost' => false, 'id' => 8002],
            ],
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/order-resources?startDate=2026-05-01&endDate=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $content['orders']);
        $this->assertSame('AL-4001', $content['orders'][0]['orderNumber']);
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
