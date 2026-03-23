<?php

namespace App\Module\Agreement\Repository\Interface;

use App\Entity\AgreementLine;

interface AgreementLineRepositoryInterface
{
    /**
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return AgreementLine|null
     */
    public function find(mixed $id, int|null $lockMode = null, int|null $lockVersion = null);

    public function save(AgreementLine $agreementLine, bool $flush = true): void;
}
