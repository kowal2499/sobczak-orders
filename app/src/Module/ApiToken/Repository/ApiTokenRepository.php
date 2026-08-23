<?php

namespace App\Module\ApiToken\Repository;

use App\Entity\User;
use App\Module\ApiToken\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findOneByHash(string $tokenHash): ?ApiToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    /**
     * @return ApiToken[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function save(ApiToken $token, bool $flush = true): void
    {
        $this->getEntityManager()->persist($token);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiToken $token, bool $flush = true): void
    {
        $this->getEntityManager()->remove($token);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
