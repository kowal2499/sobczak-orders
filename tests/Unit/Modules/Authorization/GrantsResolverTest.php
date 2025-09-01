<?php

namespace App\Tests\Unit\Modules\Authorization;
use App\Module\Authorization\Repository\Test\AuthGrantTestRepository;
use App\Module\Authorization\Repository\Test\AuthRoleGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthRoleTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserRoleTestRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\Module\Authorization\Service\GrantsResolver;
use App\Module\ModuleRegistry\Entity\Module;
use App\Module\ModuleRegistry\Repository\ModuleRepository;
use App\Repository\UserRepository;
use App\Tests\Utilities\AuthHelper;
use App\Tests\Utilities\Cache\TestCacheWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Security\Core\Security;

class GrantsResolverTest extends TestCase
{
    private GrantsResolver $rut;
    private AuthHelper $authHelper;
    private Security|MockObject $securityMock;

    protected function setUp(): void
    {
        $this->securityMock = $this->createMock(Security::class);

        $roleRepository = new AuthRoleTestRepository();
        $grantRepository = new AuthGrantTestRepository();

        $roleGrantValueRepository = new AuthRoleGrantValueTestRepository();
        $userGrantValueRepository = new AuthUserGrantValueTestRepository();
        $roleUserRepository = new AuthUserRoleTestRepository();
        $cacheService = new AuthCacheService(
            new TestCacheWrapper(),
            $this->createMock(LockFactory::class)
        );

        $this->authHelper = new AuthHelper(
            $roleRepository,
            $grantRepository,
            $roleGrantValueRepository,
            $userGrantValueRepository,
            $roleUserRepository,
            $this->createMock(ModuleRepository::class),
            $this->createMock(UserRepository::class),
        );

        $this->rut = new GrantsResolver(
            $roleGrantValueRepository,
            $userGrantValueRepository,
            $roleUserRepository,
            $this->securityMock,
            $cacheService,
        );
    }

    public function testShouldGetEmptyArrayWhenNoRoleAndUserGrants(): void
    {
        // Given
        $user = $this->authHelper->createUser();
        // When
        $roles = $this->rut->getGrants($user);
        // Then
        $this->assertEmpty($roles);
    }

    public function testShouldGetRoleGrants(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['some_grant=true', 'namespace.grant:optionOne=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['some_grant', 'namespace.grant:optionOne'], $grants);
    }

    public function testShouldGetUserGrants(): void
    {
        // Given
        $user = $this->authHelper->createUser([], [], [
            'production.dateComplete=true', 'namespace.grant:optionOne=true', 'namespace.grant:optionTwo=true'
        ]);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.dateComplete', 'namespace.grant:optionOne', 'namespace.grant:optionTwo'], $grants);
    }

    public function testShouldSkipFalsyRoleGrants(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION',
            ['production.grant01=true', 'production.grant02=false', 'production.grant03=true', 'namespace.grant:optionOne=false']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.grant01', 'production.grant03'], $grants);
    }

    public function testShouldSkipFalsyUserGrants(): void
    {
        // Given
        $user = $this->authHelper->createUser([], [], ['production.grant01=true', 'production.grant02=false', 'production.grant03=true']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.grant01', 'production.grant03'], $grants);
    }

    public function testShouldMergeRoleAndUserGrants(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=true', 'namespace.grant:optionOne=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION'], ['other.grant01=true', 'namespace.grant:optionTwo=true']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertCount(5, $grants);
        $this->assertContains('production.grant01', $grants);
        $this->assertContains('production.grant02', $grants);
        $this->assertContains('other.grant01', $grants);
        $this->assertContains('namespace.grant:optionOne', $grants);
        $this->assertContains('namespace.grant:optionTwo', $grants);
    }

    public function testShouldRemoveDuplicatedGrantsOnMerging(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=true', 'namespace.grant:optionOne=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION'], ['production.grant01=true', 'namespace.grant:optionOne=true']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.grant01', 'production.grant02', 'namespace.grant:optionOne'], $grants);
    }

    public function testShouldSkipFalsyGrantsOnMerge(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=false']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION'], ['other.grant01=true', 'other.grant02=false']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.grant01', 'other.grant01'], $grants);
    }

    public function testShouldOverrideExistingGrantWithNewValue(): void
    {
        // Given
        $this->authHelper->createRole(
            'ROLE_PRODUCTION',
            ['production.grant01=true', 'production.grant02=false', 'namespace.grant:optionOne=true', 'namespace.grant:optionTwo=true']
        );
        $user = $this->authHelper->createUser(
            [],
            ['ROLE_PRODUCTION'],
            ['production.grant01=false', 'production.grant02=true', 'namespace.grant:optionOne=false']
        );

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['production.grant02', 'namespace.grant:optionTwo'], $grants);
    }

    public function testShouldMergeRoleGrants(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['grant01=true', 'grant02=true']);
        $this->authHelper->createRole('ROLE_MARKETING', ['grant03=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION', 'ROLE_MARKETING']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertSame(['grant01', 'grant02', 'grant03'], $grants);
    }

    public function testShouldMergeRoleGrantsUsingOrOperator(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['grant01=true', 'grant02=true']);
        $this->authHelper->createRole('ROLE_MARKETING', ['grant01=true', 'grant02=false']);
        $this->authHelper->createRole('ROLE_ANALYSIS', ['grant01=false', 'grant02=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION', 'ROLE_MARKETING', 'ROLE_ANALYSIS']);

        // When
        $grants = $this->rut->getGrants($user);

        // Then
        $this->assertEqualsCanonicalizing(['grant01', 'grant02'], $grants);
    }

    public function testShouldDisableAllGrantsFromNotActiveModule(): void
    {
        // Given
        $module = new Module();
        $module->setNamespace('marketing');
        $module->setDescription('Moduł marketingu');
        $module->setActive(false);
        $this->authHelper->getOrCreateGrant('marketing.grant01', $module);
        $user = $this->authHelper->createUser([], [], ['marketing.grant01=true']);

        // When
        $grants = $this->rut->getGrants($user);

        $this->assertNotContains('marketing.grant01', $grants);
    }

    public function testShouldHaveIsGrantedMethod(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['production.grant01=true', 'production.grant02=true', 'namespace.grant:optionOne=true']);
        $user = $this->authHelper->createUser([], ['ROLE_PRODUCTION'], ['other.grant01=true', 'namespace.grant:optionTwo=true']);
        $this->securityMock->method('getUser')->willReturn($user);

        // When && Then
        $this->assertTrue($this->rut->isGranted('production.grant01'));
        $this->assertTrue($this->rut->isGranted('production.grant02'));
        $this->assertTrue($this->rut->isGranted('namespace.grant:optionOne'));
        $this->assertTrue($this->rut->isGranted('other.grant01'));
        $this->assertTrue($this->rut->isGranted('namespace.grant:optionTwo'));
        $this->assertFalse($this->rut->isGranted('other.grant02'));
        $this->assertFalse($this->rut->isGranted('namespace.grant:optionNonExist'));
    }

    public function testShouldGrantAccessIsUserHasAdminRole(): void
    {
        // Given
        $this->authHelper->createRole('ROLE_ADMINISTRATOR');
        $user = $this->authHelper->createUser([], ['ROLE_ADMINISTRATOR']);
        $this->securityMock->method('getUser')->willReturn($user);

        // When && Then
        $this->assertTrue($this->rut->isGranted('production.grant01'));
    }
}