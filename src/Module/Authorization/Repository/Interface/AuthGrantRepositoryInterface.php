<?php

namespace App\Module\Authorization\Repository\Interface;

use App\Module\Authorization\Entity\AuthGrant;

interface AuthGrantRepositoryInterface
{
    public function save(AuthGrant $grant, bool $flush = true): void;
    public function findOneBySlug(string $slug): ?AuthGrant;
}