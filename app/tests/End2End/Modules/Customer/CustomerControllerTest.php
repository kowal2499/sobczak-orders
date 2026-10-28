<?php

namespace App\Tests\End2End\Modules\Customer;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class CustomerControllerTest extends ApiTestCase
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

    public function testShouldListCustomersMatchingSearchTerm(): void
    {
        // Given
        $term = uniqid('client-');
        $this->factory->make(Customer::class, ['name' => "$term Kowalski", 'streetNumber' => '12a']);
        $this->factory->make(Customer::class, ['name' => 'unrelated']);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMERS']));

        // When
        $client->xmlHttpRequest('GET', '/api/customers', ['q' => $term]);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame("$term Kowalski", $data['items'][0]['name']);
        $this->assertSame('12a', $data['items'][0]['streetNumber']);
    }

    public function testShouldListOnlyOwnCustomersForCustomerAccount(): void
    {
        // Given
        $term = uniqid('owned-');
        $own = $this->factory->make(Customer::class, ['name' => "$term own"]);
        $this->factory->make(Customer::class, ['name' => "$term foreign"]);
        $this->getManager()->flush();
        $user = $this->createUser([], [], [], ['ROLE_CUSTOMER', 'ROLE_CUSTOMERS']);
        $user->addCustomer($own);
        $this->getManager()->flush();
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/api/customers', ['q' => $term]);

        // Then
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([$own->getId()], array_column($data['items'], 'id'));
    }

    public function testShouldListNothingForCustomerAccountWithoutCustomers(): void
    {
        // Given
        $this->factory->make(Customer::class);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMER', 'ROLE_CUSTOMERS']));

        // When
        $client->xmlHttpRequest('GET', '/api/customers');

        // Then
        $this->assertSame(0, json_decode($client->getResponse()->getContent(), true)['total']);
    }

    public function testShouldCreateCustomer(): void
    {
        // Given
        $name = uniqid('Firma ');
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMERS']));

        // When
        $client->xmlHttpRequest('POST', '/api/customers', [], [], [], json_encode([
            'name' => $name,
            'firstName' => 'Jan',
            'street' => 'Leśna',
            'postalCode' => '00-001',
            'country' => 'PL',
            'email' => 'jan@example.com',
            'phone' => '',
        ]));

        // Then
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $customer = $this->get(CustomerRepository::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($customer);
        $this->assertSame('Jan', $customer->getFirstName());
        $this->assertSame('Leśna', $customer->getStreet());
        $this->assertSame('PL', $customer->getCountry());
        $this->assertNull($customer->getPhone());
    }

    public function testShouldLetCustomerAccountUpdateOwnCustomer(): void
    {
        // Given
        $own = $this->factory->make(Customer::class, ['name' => 'before']);
        $this->getManager()->flush();
        $user = $this->createUser([], [], [], ['ROLE_CUSTOMER']);
        $user->addCustomer($own);
        $this->getManager()->flush();
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('PUT', '/api/customers/' . $own->getId(), [], [], [], json_encode([
            'name' => 'after',
            'city' => 'Poznań',
        ]));

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $customer = $this->get(CustomerRepository::class)->find($own->getId());
        $this->assertSame('after', $customer->getName());
        $this->assertSame('Poznań', $customer->getCity());
    }

    /**
     * @dataProvider foreignCustomerRequests
     */
    public function testShouldDenyCustomerAccountForeignCustomer(string $method): void
    {
        // Given
        $foreign = $this->factory->make(Customer::class);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMER']));

        // When
        $client->xmlHttpRequest($method, '/api/customers/' . $foreign->getId(), [], [], [], json_encode(['name' => 'x']));

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignCustomerRequests(): iterable
    {
        yield 'read' => ['GET'];
        yield 'update' => ['PUT'];
    }

    public function testShouldDenyListAndCreateWithoutCustomersRole(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMER']));

        // When
        $client->xmlHttpRequest('GET', '/api/customers');
        $listStatus = $client->getResponse()->getStatusCode();
        $client->xmlHttpRequest('POST', '/api/customers', [], [], [], json_encode(['name' => 'x']));

        // Then
        $this->assertSame(403, $listStatus);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testShouldRejectInvalidCustomer(array $payload, string $field): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMERS']));

        // When
        $client->xmlHttpRequest('POST', '/api/customers', [], [], [], json_encode($payload));

        // Then
        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame($field, json_decode($client->getResponse()->getContent(), true)['field']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'blank name' => [['name' => ''], 'name'];
        yield 'invalid email' => [['name' => 'x', 'email' => 'not-an-email'], 'email'];
        yield 'unknown country' => [['name' => 'x', 'country' => 'XX'], 'country'];
    }

    public function testShouldListCountriesInRequestLocale(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMER']));

        // When
        $client->xmlHttpRequest('GET', '/api/customers/countries');

        // Then
        $countries = array_column(json_decode($client->getResponse()->getContent(), true), 'name', 'code');
        $this->assertSame('Polska', $countries['PL']);
    }
}
