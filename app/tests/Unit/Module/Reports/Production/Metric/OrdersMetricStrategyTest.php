<?php

namespace App\Tests\Unit\Module\Reports\Production\Metric;

use App\Entity\Customer;
use App\Entity\User;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Reports\Production\Metric\OrdersFinishedMetricStrategy;
use App\Module\Reports\Production\Metric\OrdersPendingMetricStrategy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class OrdersMetricStrategyTest extends TestCase
{
    public function testPendingDelegatesToRepositoryWithEnd(): void
    {
        $end = new \DateTime('2026-05-31');
        $expected = ['factors_summary' => '5', 'count' => '2'];

        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->expects($this->once())->method('getPendingSummary')->with($end)->willReturn($expected);

        $strategy = new OrdersPendingMetricStrategy($repo, $this->createMock(Security::class));

        $this->assertSame($expected, $strategy->compute(new \DateTime('2026-05-01'), $end));
    }

    public function testFinishedWithoutRoleCustomerPassesNullFilter(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_CUSTOMER')->willReturn(false);

        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->expects($this->once())
            ->method('getFinishedSummary')
            ->with($this->anything(), $this->anything(), null)
            ->willReturn(['factors_summary' => null, 'count' => '0']);

        $strategy = new OrdersFinishedMetricStrategy($repo, $security);
        $strategy->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));
    }

    public function testFinishedWithRoleCustomerPassesOwnedCustomerIds(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(42);

        $user = $this->createMock(User::class);
        $user->method('getCustomers')->willReturn(new ArrayCollection([$customer]));

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_CUSTOMER')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->expects($this->once())
            ->method('getFinishedSummary')
            ->with($this->anything(), $this->anything(), [42])
            ->willReturn(['factors_summary' => '4', 'count' => '1']);

        $strategy = new OrdersFinishedMetricStrategy($repo, $security);
        $strategy->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));
    }

    public function testFinishedWithRoleCustomerButNoCustomersPassesNullFilter(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCustomers')->willReturn(new ArrayCollection([]));

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_CUSTOMER')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->expects($this->once())
            ->method('getFinishedSummary')
            ->with($this->anything(), $this->anything(), null)
            ->willReturn(['factors_summary' => null, 'count' => '0']);

        $strategy = new OrdersFinishedMetricStrategy($repo, $security);
        $strategy->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));
    }
}
