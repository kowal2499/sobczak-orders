<?php

namespace App\Module\Authorization\Voter;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Production;
use App\Entity\User;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Task\Entity\Task;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

/**
 * ASSIGNED_CUSTOMER - czy rekord należy do klienta przypisanego użytkownikowi z ROLE_CUSTOMER.
 * Użytkownik bez ROLE_CUSTOMER przechodzi zawsze.
 */
class CustomerVoter extends Voter
{
    public const ASSIGNED_CUSTOMER = 'ASSIGNED_CUSTOMER';

    private const SUBJECTS = [
        Customer::class,
        Agreement::class,
        AgreementLine::class,
        AgreementLineRM::class,
        Production::class,
        Task::class,
        Attachment::class,
    ];

    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function supports($attribute, $subject): bool
    {
        if ($attribute !== self::ASSIGNED_CUSTOMER || !is_object($subject)) {
            return false;
        }
        foreach (self::SUBJECTS as $class) {
            if ($subject instanceof $class) {
                return true;
            }
        }

        return false;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        if (false === $this->security->isGranted('ROLE_CUSTOMER')) {
            return true;
        }

        $customerId = $this->customerIdOf($subject);
        if ($customerId === null) {
            return false;
        }

        foreach ($user->getCustomers() as $customer) {
            if ($customer?->getId() === $customerId) {
                return true;
            }
        }

        return false;
    }

    private function customerIdOf(object $subject): ?int
    {
        return match (true) {
            $subject instanceof Customer => $subject->getId(),
            $subject instanceof Agreement => $subject->getCustomer()?->getId(),
            $subject instanceof AgreementLine => $subject->getAgreement()?->getCustomer()?->getId(),
            $subject instanceof AgreementLineRM => $subject->getCustomerId(),
            $subject instanceof Production => $subject->getAgreementLine()?->getAgreement()?->getCustomer()?->getId(),
            $subject instanceof Task => $subject->getAgreementLine()->getAgreement()?->getCustomer()?->getId(),
            $subject instanceof Attachment => $subject->getAgreement()?->getCustomer()?->getId(),
            default => null,
        };
    }
}
