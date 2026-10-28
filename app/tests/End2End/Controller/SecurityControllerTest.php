<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Customer;
use App\Entity\Customers2Users;
use App\Entity\User;
use App\Repository\UserRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class SecurityControllerTest extends ApiTestCase
{
    /** @var Customer */
    private $customer;
    /** @var User */
    private $user;
    /** @var \Faker\Generator */
    private $faker;

    protected function setUp(): void
    {
        $this->getManager()->beginTransaction();
        $factory = new EntityFactory($this->getManager());
        $this->faker = $factory->getFaker();
        $this->customer = $factory->make(Customer::class);
        $this->user = $factory->make(User::class, ['roles' => ['ROLE_ADMIN']]);
        $this->getManager()->flush();
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateUserWithCustomerRoleAndAssignedCustomers(): void
    {
        // Given
        $client = $this->login($this->user);
        $userEmail = $this->faker->email;

        // When
        $client->xmlHttpRequest('POST', '/user', [
            'email' => $userEmail,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'passwordPlain' => 'secret123',
            'roles' => ['ROLE_CUSTOMER'],
            'customers2Users' => [['customer' => $this->customer->getId()]]
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $newUserId = (json_decode($client->getResponse()->getContent(), true) ?? [])['id'];

        /** @var UserRepository $repository */
        $repository = $this->getManager()->getRepository(User::class);

        $newUser = $repository->find($newUserId);

        $this->assertEquals($userEmail, $newUser->getEmail());
        $this->assertEquals('John', $newUser->getFirstName());
        $this->assertEquals('Doe', $newUser->getLastName());
        $this->assertEquals(['ROLE_CUSTOMER'], $newUser->getRoles());

        $c2u = $newUser->getCustomers2Users();
        /** @var Customers2Users $connection */
        $connection = $c2u->get(0);
        $this->assertCount(1, $c2u);
        $this->assertEquals($connection->getCustomer()->getId(), $this->customer->getId());
        $this->assertEquals($connection->getUser()->getId(), $newUserId);
    }

    public function testShouldReturnAuthRolesWithFetchedUsers(): void
    {
        // Given
        $withRoles = $this->createUser([], ['Kierownik produkcji', 'Handlowiec']);
        $withoutRoles = $this->createUser();
        $this->getManager()->flush();

        $client = $this->login($this->user);

        // When
        $client->xmlHttpRequest('GET', '/users/fetch?all=true');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $users = json_decode($client->getResponse()->getContent(), true);
        $byId = array_column($users, null, 'id');

        $this->assertEquals(
            ['Handlowiec', 'Kierownik produkcji'],
            $byId[$withRoles->getId()]['authRoles']
        );
        $this->assertEquals([], $byId[$withoutRoles->getId()]['authRoles']);
        $this->assertArrayHasKey('email', $byId[$withoutRoles->getId()]);
        $this->assertArrayHasKey('active', $byId[$withoutRoles->getId()]);
    }
}