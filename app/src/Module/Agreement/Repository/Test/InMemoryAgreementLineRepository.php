<?php

namespace App\Module\Agreement\Repository\Test;

use App\Entity\AgreementLine;
use App\Module\Agreement\Repository\Interface\AgreementLineRepositoryInterface;

class InMemoryAgreementLineRepository implements AgreementLineRepositoryInterface
{
    /**
     * @var array<int, AgreementLine>
     */
    private array $storage = [];

    public function find(mixed $id, int|null $lockMode = null, int|null $lockVersion = null)
    {
        return $this->storage[$id] ?? null;
    }

    public function save(AgreementLine $agreementLine, bool $flush = true): void
    {
        $id = $agreementLine->getId();

        if ($id === null) {
            throw new \InvalidArgumentException('AgreementLine must have an ID before saving to InMemoryRepository');
        }

        $this->storage[$id] = $agreementLine;
    }
}
