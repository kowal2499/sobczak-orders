<?php

namespace App\Tests\End2End\Modules\Spa;

use App\Entity\Customer;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class MeControllerTest extends ApiTestCase
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

    public function testShouldReturnCurrentUserWithReachableRolesGrantsAndCustomers(): void
    {
        // Given
        $this->getAuthHelper()->createRole('ROLE_SPA_TEST', ['grant02=true']);
        $user = $this->createUser([], ['ROLE_SPA_TEST'], ['grant04=true', 'grant05:option01=true'], ['ROLE_CUSTOMER']);
        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $this->getManager()->flush();
        $user->addCustomer($customerA);
        $user->addCustomer($customerB);
        $this->getManager()->flush();

        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/me');

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame($user->getId(), $data['id']);
        $this->assertSame($user->getEmail(), $data['email']);
        $this->assertSame($user->getFirstName(), $data['firstName']);
        $this->assertSame($user->getLastName(), $data['lastName']);
        $this->assertSame('pl', $data['locale']);
        $this->assertFalse($data['isImpersonated']);

        $this->assertContains('ROLE_CUSTOMER', $data['roles']);
        $this->assertContains('ROLE_CUSTOMERS_LIMITED', $data['roles']);
        $this->assertContains('ROLE_PRODUCTION_VIEW', $data['roles']);
        $this->assertNotContains('ROLE_ADMIN', $data['roles']);
        $this->assertNotContains('ROLE_PRODUCTS', $data['roles']);

        $this->assertSame(['grant02', 'grant04', 'grant05:option01'], $data['grants']);

        $expectedCustomers = [$customerA->getId(), $customerB->getId()];
        sort($expectedCustomers);
        $actualCustomers = $data['customerIds'];
        sort($actualCustomers);
        $this->assertSame($expectedCustomers, $actualCustomers);
    }
}
