<?php

namespace App\Module\Agreement\Repository\Test;

use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\Interface\AgreementLineRMRepositoryInterface;

class InMemoryAgreementLineRMRepository implements AgreementLineRMRepositoryInterface
{
    /**
     * @var array<int, AgreementLineRM>
     */
    private array $storage = [];

    public function find(mixed $id, int|null $lockMode = null, int|null $lockVersion = null)
    {
        return $this->storage[$id] ?? null;
    }

    public function remove(AgreementLineRM $agreementLineRM, bool $flush = true): void
    {
        $id = $agreementLineRM->getAgreementLineId();

        if (isset($this->storage[$id])) {
            unset($this->storage[$id]);
        }
    }

    public function add(AgreementLineRM $agreementLineRM, bool $flush = true): void
    {
        $id = $agreementLineRM->getAgreementLineId();
        $this->storage[$id] = $agreementLineRM;
    }
}
