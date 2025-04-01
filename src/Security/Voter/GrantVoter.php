<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GrantVoter implements VoterInterface
{

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // na wzór: App\Module\Security\Authorization\Infrastructure\Security
        // bin/console debug:container --tag=security.voter

        // kolekcja grantów jest pobierana z tokenu
        // i weryfikowana z elementami z $attributes

        // trzeba zbudować tablicę grantów,
        // w tym celu tworzymy serwis który dla danego usera
        // pobierze granty oraz role. role trzeba rozpakować by wydobyć z nich granty.
        // tak przygotowaną kolekcję zapisać w cache i zwracać w tym voterze
        // patrz: GrantsResolver

        return VoterInterface::ACCESS_GRANTED;
    }
}