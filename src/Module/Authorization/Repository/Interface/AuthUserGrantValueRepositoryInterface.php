<?php

namespace App\Module\Authorization\Repository\Interface;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\Test\AuthUserGrantValueTestRepository;

interface AuthUserGrantValueRepositoryInterface
{
    public function findOneByUserAndGrant(User $user, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthUserGrantValue;
    public function findAllByUser(User $user): array;
    public function add(AuthUserGrantValue $userGrantValue): void;

}