<?php

namespace App\Module\Authorization\Repository\Test;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;

class AuthUserGrantValueTestRepository implements AuthUserGrantValueRepositoryInterface
{
    /** @var AuthUserGrantValue[] */
    private array $storage = [];

    public function findOneByUserAndGrant(User $user, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthUserGrantValue
    {
        return null;
    }

    public function findAllByUser(User $user): array
    {
        return array_filter($this->storage, fn(AuthUserGrantValue $item) => $item->getUser()->getId() === $user->getId());
    }

    public function add(AuthUserGrantValue $userGrantValue): void
    {
        if (!$this->innerFind($userGrantValue)) {
            $this->storage[] = $userGrantValue;
        }
    }

    private function innerFind(AuthUserGrantValue $userGrantValue): ?AuthUserGrantValue
    {
        foreach ($this->storage as $ugValue) {
            if (
                $ugValue->getUser()->getId() === $userGrantValue->getUser()->getId()
                && $ugValue->getGrant()->getId() === $userGrantValue->getGrant()->getId()
                && $ugValue->getGrantOptionSlug() === $userGrantValue->getGrantOptionSlug()
            ) {
                return $ugValue;
            }
        }
        return null;
    }
}