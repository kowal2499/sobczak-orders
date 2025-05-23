<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;

class AuthGrantTestRepository implements AuthGrantRepositoryInterface
{
    private array $grantsMap = [];
    public function add(AuthGrant $grant, bool $flush = true): void
    {
        if (!$this->findById($grant->getId())) {
            $this->grantsMap[$grant->getId()] = $grant;
        }
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