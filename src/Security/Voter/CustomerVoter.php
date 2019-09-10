<?php

namespace App\Security\Voter;

use App\Entity\Customer;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomerVoter extends Voter
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['ASSIGNED_CUSTOMER'])
            && $subject instanceof Customer;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Customer $subject */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'ASSIGNED_CUSTOMER':

                if ($this->security->isGranted('ROLE_CUSTOMERS')) {
                    return true;
                }

                /** @var $user User */
                $user = $this->security->getUser();
                $customersIds = array_map(function($customer) { return $customer->getId(); }, $user->getCustomers());

                return in_array($subject->getId(), $customersIds);
        }

        return false;
    }
}
