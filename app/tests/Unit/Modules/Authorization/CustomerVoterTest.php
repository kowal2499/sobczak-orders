<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Production;
use App\Entity\User;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Authorization\Voter\CustomerVoter;
use App\Module\Task\Entity\Task;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;

class CustomerVoterTest extends TestCase
{
    private const OWN = 1;
    private const FOREIGN = 2;

    /**
     * @return iterable<string, array{\Closure(int): object}>
     */
    public static function subjects(): iterable
    {
        yield 'customer' => [fn (int $id) => self::customer($id)];
        yield 'agreement' => [fn (int $id) => self::agreement($id)];
        yield 'agreement line' => [fn (int $id) => self::line($id)];
        yield 'production' => [fn (int $id) => (new Production())->setAgreementLine(self::line($id))];
        yield 'task' => [fn (int $id) => (new Task())->setAgreementLine(self::line($id))];
        yield 'attachment' => [fn (int $id) => (new Attachment())->setAgreement(self::agreement($id))];
        yield 'read model line' => [function (int $id) {
            $rm = new AgreementLineRM(10);
            $rm->setCustomerId($id);

            return $rm;
        }];
    }

    /**
     * @dataProvider subjects
     */
    public function testShouldGrantOwnCustomerRecord(\Closure $make): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($make(self::OWN), true));
    }

    /**
     * @dataProvider subjects
     */
    public function testShouldDenyForeignCustomerRecord(\Closure $make): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($make(self::FOREIGN), true));
    }

    /**
     * @dataProvider subjects
     */
    public function testShouldGrantAnyRecordWithoutRoleCustomer(\Closure $make): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($make(self::FOREIGN), false));
    }

    public function testShouldAbstainOnUnsupportedSubject(): void
    {
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new \stdClass(), true));
    }

    private function vote(object $subject, bool $isCustomer): int
    {
        $user = $this->createMock(User::class);
        $user->method('getCustomers')->willReturn(new ArrayCollection([self::customer(self::OWN)]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_CUSTOMER')->willReturn($isCustomer);

        return (new CustomerVoter($security))->vote($token, $subject, [CustomerVoter::ASSIGNED_CUSTOMER]);
    }

    private static function customer(int $id): Customer
    {
        $customer = new Customer();
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, $id);

        return $customer;
    }

    private static function agreement(int $customerId): Agreement
    {
        return (new Agreement())->setCustomer(self::customer($customerId));
    }

    private static function line(int $customerId): AgreementLine
    {
        return (new AgreementLine())->setAgreement(self::agreement($customerId));
    }
}
