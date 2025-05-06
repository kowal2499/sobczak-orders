<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthRoleRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use App\Module\Authorization\Repository\Test\AuthGrantTestRepository;
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
    private AuthRoleRepositoryInterface $roleRepository;
    private AuthGrantRepositoryInterface $grantRepository;
    private AuthRoleGrantValueRepositoryInterface $roleGrantValueRepository;
    private AuthUserRoleRepositoryInterface $roleUserRepository;
    private \Faker\Generator $faker;
    private AuthUserGrantValueTestRepository $roleUserValueRepository;

    protected function setUp(): void
    {
        $this->faker = Factory::create('pl_PL');
        $this->roleRepository = new AuthRoleTestRepository();
        $this->grantRepository = new AuthGrantTestRepository();
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
        $grantProductionPanel = GrantVO::m('production.panel');
        $grant = $this->createGrant($grantProductionPanel);
        $role = $this->createRole('ROLE_PRODUCTION', [new GrantValue($grantProductionPanel)]);
        $user = $this->createUser([], [$role]);

        // When
        $roles = $this->rot->resolve($user);

        // Then
        $this->assertEquals(['production.panel'], $roles);
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
        PrivateProperty::setId($user, $data['id'] ?? null);
        $user->setEmail($data['email'] ?? $this->faker->email);

        foreach ($roles as $role) {
            $this->roleUserRepository->add(new AuthUserRole($user, $role));
        }

        return $user;
    }

    /**
     * @param string $name
     * @param GrantValue[] $grantValues
     * @return AuthRole
     */
    private function createRole(string $name, array $grantValues): AuthRole
    {
        $role = new AuthRole();
        PrivateProperty::setId($role);
        $role->setName($name);
        $this->roleRepository->add($role);

        foreach ($grantValues as $grantValue) {
            $grantVO = $grantValue->getGrantVO();
            $grant = $this->grantRepository->findOneBySlug($grantVO->getBaseSlug());
            $roleGrantValue = new AuthRoleGrantValue($role, $grant, $grantVO->getOptionSlug());
            $roleGrantValue->setValue($grantValue->getValue());
            $this->roleGrantValueRepository->add($roleGrantValue);
        }
        return $role;
    }

    private function createGrant(GrantVO $m): AuthGrant
    {
        $grant = new AuthGrant();
        PrivateProperty::setId($grant);
        $grant->setSlug($m->getBaseSlug());
        $this->grantRepository->save($grant);

        return $grant;
    }
}