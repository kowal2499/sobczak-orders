<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class GrantRoleValueControllerTest extends ApiTestCase
{
    private AuthRoleRepository $roleRepository;
    private AuthGrantRepository $grantRepository;

    protected function setUp(): void
    {
        $this->getManager()->beginTransaction();
        $factory = new EntityFactory($this->getManager());

        $this->roleRepository = $this->get(AuthRoleRepository::class);
        $this->grantRepository = $this->get(AuthGrantRepository::class);
    }

    public function testShouldAddGrantRoleValue(): void
    {
        // Given
        $grant = $this->getAuthHelper()->createGrant('grant768');
        $role = $this->getAuthHelper()->createRole('ROLE_PRODUCTION', []);

        $user = $this->createUser([], ['ROLE_ADMIN']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('POST', '/authorization/grant/role/' . $role->getId() . '/value', [[
            'role_id' => $role->getId(),
            'grant_id' => $grant->getId(),
            'grant_option_slug' => null,
            'value' => true
        ]]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /** @var AuthRoleGrantValueRepository $repo */
        $repo = $this->getContainer()->get(AuthRoleGrantValueRepository::class);
        $entity = $repo->findOneByRoleAndGrant(
            $this->roleRepository->find($role->getId()),
            $this->grantRepository->find($grant->getId()),
        );
        $this->assertNotNull($entity);
        $this->assertTrue($entity->getValue());
    }

    public function testShouldListGrantRoleValues(): void
    {
        // Given
        $user = $this->createUser([], ['ADMIN']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/authorization/grant/role/value');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
