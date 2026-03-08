<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Repository;

use App\Module\Tag\Entity\TagDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class TagDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagDefinition::class);
    }

    public function notDeleted(): Query
    {
        return $this->createQueryBuilder('t')
            ->where('t.isDeleted = false')
            ->getQuery();
    }

    public function findByModule(string $module): Query
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDeleted = false')
            ->andWhere('t.module = :m')
            ->setParameter('m', $module)
            ->getQuery();
    }

    public function findBySlug(string $slug): ?TagDefinition
    {
        /** @var TagDefinition|null $result */
        $result = $this->findOneBy(['slug' => $slug]);

        return $result;
    }

    public function findAllById(array $ids): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDeleted = false')
            ->andWhere('t.id IN (:idarray)')
            ->setParameter('idarray', $ids)
            ->getQuery()
            ->getResult();
    }
}
