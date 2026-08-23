<?php

namespace App\Tests\Unit\Modules\BonusSettlement;

use App\Entity\User;
use App\Module\Authorization\Service\GrantsResolver;
use App\Module\BonusSettlement\Service\DepartmentMembershipProvider;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class DepartmentMembershipProviderTest extends TestCase
{
    public function testShouldGiveOneMembershipPerDepartmentGrant(): void
    {
        // Given
        $user = $this->makeUser('Jan', 'Kowalski');
        $provider = $this->makeProvider([$user], [
            'production.show.laquering',
            'production.show.packing',
        ]);

        // When
        $memberships = $provider->all();

        // Then
        $this->assertCount(2, $memberships);
        $this->assertSame(['dpt04', 'dpt05'], array_map(fn ($m) => $m->departmentSlug, $memberships));
        $this->assertSame('Lakierowanie', $memberships[0]->departmentLabel);
        $this->assertSame('Jan Kowalski', $memberships[0]->userLabel);
        $this->assertSame($user, $memberships[0]->user);
    }

    public function testShouldSkipUserWithoutDepartmentGrant(): void
    {
        // Given
        $provider = $this->makeProvider([$this->makeUser('Anna', 'Nowak')], ['orders.create']);

        // When
        $memberships = $provider->all();

        // Then
        $this->assertSame([], $memberships);
    }

    /**
     * isGranted() zwraca true na wszystko dla authorization.admin. Gdyby provider go używał,
     * każdy administrator dostałby premię z sześciu działów naraz.
     */
    public function testShouldNotGrantAllDepartmentsToAdmin(): void
    {
        // Given
        $provider = $this->makeProvider([$this->makeUser('Admin', 'Adminowski')], [
            GrantsResolver::ADMIN_GRANT,
        ]);

        // When
        $memberships = $provider->all();

        // Then
        $this->assertSame([], $memberships);
    }

    public function testShouldFallBackToUsernameWhenNameIsMissing(): void
    {
        // Given
        $user = new User();
        $user->setEmail('brygadzista@erla.pl');
        $provider = $this->makeProvider([$user], ['production.show.cnc']);

        // When
        $memberships = $provider->all();

        // Then
        $this->assertSame('brygadzista@erla.pl', $memberships[0]->userLabel);
    }

    /**
     * @param User[] $activeUsers
     * @param string[] $grants
     */
    private function makeProvider(array $activeUsers, array $grants): DepartmentMembershipProvider
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findBy')->willReturn($activeUsers);

        $grantsResolver = $this->createMock(GrantsResolver::class);
        $grantsResolver->method('getGrants')->willReturn($grants);

        return new DepartmentMembershipProvider($userRepository, $grantsResolver);
    }

    private function makeUser(string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        return $user;
    }
}
