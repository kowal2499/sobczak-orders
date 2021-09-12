<?php

namespace App\Modules\Reports\Production\Repository;

use App\Modules\Reports\Production\Model\ProductionModelCollection;

interface ProductionRepositoryInterface
{
    public function findFinishedByDate(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): ProductionModelCollection;

    public function findPendingByDate(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): ProductionModelCollection;

}