<?php

namespace App\Module\Agreement\Repository\Interface;

use App\Module\Agreement\ReadModel\AgreementLineRM;

interface AgreementLineRMRepositoryInterface
{
    /**
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return AgreementLineRM|null
     */
    public function find(mixed $id, int|null $lockMode = null, int|null $lockVersion = null);

    public function remove(AgreementLineRM $agreementLineRM, bool $flush = true): void;

    public function add(AgreementLineRM $agreementLineRM, bool $flush = true): void;
}
