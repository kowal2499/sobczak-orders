<?php

namespace App\Module\UserSetting\Repository;

use App\Entity\User;
use App\Module\UserSetting\Entity\UserSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSetting>
 */
class UserSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSetting::class);
    }

    public function findOneByUserAndContext(User $user, string $context): ?UserSetting
    {
        return $this->findOneBy(['user' => $user, 'context' => $context]);
    }

    public function findOneByUserIdAndContext(int $userId, string $context): ?UserSetting
    {
        return $this->findOneBy(['user' => $userId, 'context' => $context]);
    }

    public function save(UserSetting $userSetting, bool $flush = true): void
    {
        $this->getEntityManager()->persist($userSetting);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
