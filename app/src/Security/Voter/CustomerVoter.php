<?php

namespace App\Security\Voter;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomerVoter extends Voter
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['ASSIGNED_CUSTOMER'])
            && $subject instanceof Customer;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var $user User */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'ASSIGNED_CUSTOMER':
                // if user doesn't have ROLE_CUSTOMER than grant access
                if (false === $this->security->isGranted('ROLE_CUSTOMER')) {
                    return true;
                }
                return $user->getCustomers()->contains($subject);
        }

        return false;
    }
}
