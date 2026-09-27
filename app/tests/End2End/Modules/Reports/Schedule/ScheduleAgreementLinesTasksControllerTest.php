<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Tests\Utilities\Factory\EntityFactory;

class ScheduleAgreementLinesTasksControllerTest extends BaseScheduleReportsTestCase
{
    private const GRANT = 'reports.calendar_general';

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldReturnAgreementLinesTasksForDateRange()
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], [self::GRANT]);
        $client = $this->login($user);

        $this->createAgreementLineRM(
            1,
            'ORD-1',
            new \DateTime('2021-09-10'),
            AgreementLine::STATUS_MANUFACTURING,
            1.0,
            false,
            false,
            true
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/agreement-lines?startDate=2021-09-01&endDate=2021-09-30');

        // Then
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('agreementLineId', $data[0]);
        $this->assertArrayHasKey('hasProduction', $data[0]);
        $this->assertEquals(true, $data[0]['hasProduction']);
    }

    public function testShouldReturnOnlyOwnedCustomerLinesForRoleCustomer(): void
    {
        // Given
        $em = $this->getManager();
        $factory = new EntityFactory($em);
        $user = $this->createUser([], [], [self::GRANT], ['ROLE_CUSTOMER']);
        $client = $this->login($user);

        $ownCustomer = $factory->make(Customer::class);
        $foreignCustomer = $factory->make(Customer::class);
        $em->flush();
        $user->addCustomer($ownCustomer);

        $own = $this->createAgreementLineRM(1, 'ORD-1', new \DateTime('2021-09-10'), AgreementLine::STATUS_MANUFACTURING, 1.0, false, false, true);
        $own->setCustomerId($ownCustomer->getId());
        $foreign = $this->createAgreementLineRM(2, 'ORD-2', new \DateTime('2021-09-10'), AgreementLine::STATUS_MANUFACTURING, 1.0, false, false, true);
        $foreign->setCustomerId($foreignCustomer->getId());
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/agreement-lines?startDate=2021-09-01&endDate=2021-09-30');

        // Then
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['ORD-1'], array_column($data, 'orderNumber'));
    }

    public function testShouldDenyAccessWithoutGrant(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/agreement-lines?startDate=2021-09-01&endDate=2021-09-30');

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
