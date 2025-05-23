<?php

namespace App\Tests\Unit\Modules\Authorization;
use App\Module\Authorization\Service\GrantsResolver;

class GrantsResolverTest extends AuthBase
{
    private GrantsResolver $rut;

    protected function setUp(): void
    {
        $this->init();

        $this->rut = new GrantsResolver(
            $this->roleGrantValueRepository,
            $this->userGrantValueRepository,
            $this->roleUserRepository
        );
    }

    public function testShouldGetEmptyArrayWhenNoRoleAndUserGrants(): void
    {
        // Given
        $user = $this->createUser();
        // When
        $roles = $this->rut->resolve($user);
        // Then
        $this->assertEmpty($roles);
    }

    public function testShouldGetRoleGrants(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.panel=true']);
        $user = $this->createUser([], ['ROLE_PRODUCTION']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertEquals(['production.panel'], $grants);
    }

    public function testShouldGetUserGrants(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.dateComplete=true']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertEquals(['production.dateComplete'], $grants);
    }

    public function testShouldSkipFalsyRoleGrants(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=false', 'production.grant03=true']);
        $user = $this->createUser([], ['ROLE_PRODUCTION']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertEquals(['production.grant01', 'production.grant03'], $grants);
    }

    public function testShouldSkipFalsyUserGrants(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.grant01=true', 'production.grant02=false', 'production.grant03=true']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertEquals(['production.grant01', 'production.grant03'], $grants);
    }

    public function testShouldMergeRoleAndUserGrants(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=true']);
        $user = $this->createUser([], ['ROLE_PRODUCTION'], ['other.grant01=true']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertCount(3, $grants);
        $this->assertContains('production.grant01', $grants);
        $this->assertContains('production.grant02', $grants);
        $this->assertContains('other.grant01', $grants);
    }

    public function testShouldRemoveDuplicatedGrantsOnMerging(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=true']);
        $user = $this->createUser([], ['ROLE_PRODUCTION'], ['production.grant01=true']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertCount(2, $grants);
        $this->assertContains('production.grant01', $grants);
        $this->assertContains('production.grant02', $grants);
    }

    public function testShouldSkipFalsyGrantsOnMerge(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=false']);
        $user = $this->createUser([], ['ROLE_PRODUCTION'], ['other.grant01=true', 'other.grant02=false']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertCount(2, $grants);
        $this->assertContains('production.grant01', $grants);
        $this->assertContains('other.grant01', $grants);
    }

    public function testShouldOverrideExistingGrantWithNewValue(): void
    {
        // Given
        $this->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=false']);
        $user = $this->createUser([], ['ROLE_PRODUCTION'], ['production.grant01=false', 'production.grant02=true']);

        // When
        $grants = $this->rut->resolve($user);

        // Then
        $this->assertCount(1, $grants);
        $this->assertContains('production.grant02', $grants);
    }
}