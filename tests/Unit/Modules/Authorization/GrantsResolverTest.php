<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\Authorization\Repository\Test\AuthRoleGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthRoleTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserRoleTestRepository;
use App\Module\Authorization\Service\GrantsResolver;
use App\Module\Authorization\ValueObject\GrantValue;
use App\Module\Authorization\ValueObject\GrantVO;
use App\Tests\Utilities\PrivateProperty;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class GrantsResolverTest extends TestCase
{
    private GrantsResolver $rot;
    private AuthRoleTestRepository $roleRepository;
    private AuthRoleGrantValueTestRepository $roleGrantValueRepository;
    private AuthUserRoleTestRepository $roleUserRepository;
    private \Faker\Generator $faker;
    private AuthUserGrantValueTestRepository $roleUserValueRepository;

    protected function setUp(): void
    {
        $this->faker = Factory::create('pl_PL');
        $this->roleRepository = new AuthRoleTestRepository();
        $this->roleGrantValueRepository = new AuthRoleGrantValueTestRepository();
        $this->roleUserValueRepository = new AuthUserGrantValueTestRepository();
        $this->roleUserRepository = new AuthUserRoleTestRepository();
        $this->rot = new GrantsResolver(
            $this->roleGrantValueRepository,
            $this->roleUserValueRepository,
            $this->roleUserRepository
        );
    }

    public function testShouldGetEmptyArrayWhenNoRoleAndUserGrants(): void
    {
        // Given
        $user = $this->createUser();

        // When
        $roles = $this->rot->resolve($user);

        // Then
        $this->assertEmpty($roles);
    }

    public function testShouldGetRoleGrants(): void
    {
        // Given
        $grant = $this->createGrant(GrantVO::m('production.panel'));
        $role = $this->createRole('ROLE_PRODUCTION', [new GrantValue(GrantVO::m('production.panel'))]);
        $user = $this->createUser([], [$role]);

        $this->markTestIncomplete();
    }

    public function testShouldGetUserGrants(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldSkipFalsyRoleGrants(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldSkipFalsyUserGrants(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldMergeRoleAndUserGrants(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldRemoveDuplicatedGrants(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldSkipFalsyGrantsOnMerge(): void
    {
        $this->markTestIncomplete();
    }

    public function testShouldSkipGrantWereFalsyAtLeastOnce(): void
    {
        $this->markTestIncomplete();
    }

    public function createUser(array $data = [], array $roles = []): User
    {
        $user = new User();
        PrivateProperty::setId($data['id'] ?? $this->faker->randomNumber(3), $user);
        $user->setEmail($data['email'] ?? $this->faker->email);

        foreach ($roles as $role) {
            $this->roleUserRepository->add(new AuthUserRole($user, $role));
        }

        return $user;
    }

    /**
     * @param string $name
     * @param GrantValue[] $grants
     * @return AuthRole
     */
    private function createRole(string $name, array $grants): AuthRole
    {
        $role = new AuthRole();
        $role->setName($name);
        $this->roleRepository->add($role);

        foreach ($grants as $grant) {
            $roleGrantValue = new AuthRoleGrantValue($role, $grant, $grant->getGrantVO()->getOptionSlug());
            $roleGrantValue->setValue($grant->getValue());
            $this->roleGrantValueRepository->add($roleGrantValue);
        }
        return $role;
    }

    private function createGrant(GrantVO $m)
    {
        // todo: here
    }
}