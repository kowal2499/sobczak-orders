<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\Authorization\ValueObject\GrantVO;
use App\Tests\Utilities\PrivateProperty;

class AuthGrantTestRepository implements AuthGrantRepositoryInterface
{
    private array $grantsMap = [];
    public function add(AuthGrant $grant, bool $flush = true): void
    {
        PrivateProperty::setId($grant);
        $this->grantsMap[$grant->getId()] = $grant;
    }

    public function findOneBySlug(string $slug): ?AuthGrant
    {
        foreach ($this->grantsMap as $grant) {
            if ($grant->getSlug() === $slug) {
                return $grant;
            }
        }
        return null;
    }

    private function findById(int $id): ?AuthGrant
    {
        return $this->grantsMap[$id] ?? null;
    }
}