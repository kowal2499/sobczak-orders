<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Tests\Utilities\Factory\EntityFactory;

class ScheduleCapacityOwnershipTest extends BaseScheduleReportsTestCase
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

    public function testShouldReturnAllAgreementLinesForUnrestrictedUser(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $em->flush();

        $rmA = $this->createAgreementLineRM(101, 'AL-101', new \DateTime('2026-03-03'), AgreementLine::STATUS_MANUFACTURING, 1.0, false, false, true);
        $rmA->setCustomerId($customerA->getId());

        $rmB = $this->createAgreementLineRM(102, 'AL-102', new \DateTime('2026-03-03'), AgreementLine::STATUS_MANUFACTURING, 2.0, false, false, true);
        $rmB->setCustomerId($customerB->getId());

        $this->createCapacity(new \DateTime('2026-03-03'), 5.0);
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/capacity?startDate=2026-03-03&endDate=2026-03-03');

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $content);
        $this->assertEquals(3.0, $content[0]['capacityBurned']);
        $this->assertCount(2, $content[0]['agreementLines']);
    }

    public function testShouldReturnOnlyOwnedCustomerAgreementLinesForRoleCustomerUser(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity'], ['ROLE_CUSTOMER']);
        $client = $this->login($user);

        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $em->flush();

        $user->addCustomer($customerA);
        $em->flush();

        $rmA = $this->createAgreementLineRM(201, 'AL-201', new \DateTime('2026-03-03'), AgreementLine::STATUS_MANUFACTURING, 1.0, false, false, true);
        $rmA->setCustomerId($customerA->getId());

        $rmB = $this->createAgreementLineRM(202, 'AL-202', new \DateTime('2026-03-03'), AgreementLine::STATUS_MANUFACTURING, 2.0, false, false, true);
        $rmB->setCustomerId($customerB->getId());

        $this->createCapacity(new \DateTime('2026-03-03'), 5.0);
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/capacity?startDate=2026-03-03&endDate=2026-03-03');

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $content);

        // capacityBurned liczy dla całej firmy (obu klientów)
        $this->assertEquals(3.0, $content[0]['capacityBurned']);

        // agreementLines zawiera tylko zlecenia przypisanego klienta A
        $this->assertCount(1, $content[0]['agreementLines']);
        $this->assertEquals('AL-201', $content[0]['agreementLines'][0]['orderNumber']);
    }

    public function testShouldReturnEmptyAgreementLinesWhenRoleCustomerUserHasNoCustomersAssigned(): void
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], ['work-configuration.capacity'], ['ROLE_CUSTOMER']);
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $em->flush();

        $rm = $this->createAgreementLineRM(301, 'AL-301', new \DateTime('2026-03-03'), AgreementLine::STATUS_MANUFACTURING, 1.5, false, false, true);
        $rm->setCustomerId($customer->getId());

        $this->createCapacity(new \DateTime('2026-03-03'), 5.0);
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/capacity?startDate=2026-03-03&endDate=2026-03-03');

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $content);

        // capacityBurned nadal liczy całość
        $this->assertEquals(1.5, $content[0]['capacityBurned']);

        // agreementLines puste - brak przypisanych klientów
        $this->assertCount(0, $content[0]['agreementLines']);
    }
}
