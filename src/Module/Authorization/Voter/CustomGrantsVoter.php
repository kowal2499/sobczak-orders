<?php

namespace App\Module\Authorization\Voter;

use App\Entity\User;
use App\Module\Authorization\Service\GrantsResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CustomGrantsVoter extends Voter
{

    public function __construct(
        private readonly GrantsResolver $grantsResolver
    ) {
    }

    protected function supports(string $attribute, $subject)
    {
        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->grantsResolver->isGranted($attribute);
    }
}