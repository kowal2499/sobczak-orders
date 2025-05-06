<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;

class AuthGrantTestRepository implements AuthGrantRepositoryInterface
{
    private array $grantsMap = [];
    public function save(AuthGrant $grant, bool $flush = true): void
    {
        if (!$this->findOneBySlug($grant->getSlug())) {
            $this->grantsMap[$grant->getSlug()] = $grant;
        }
    }

    public function findOneBySlug(string $slug): ?AuthGrant
    {
        return $this->grantsMap[$slug] ?? null;
    }
}