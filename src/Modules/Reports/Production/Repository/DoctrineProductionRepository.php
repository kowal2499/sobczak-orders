<?php

namespace App\Modules\Reports\Production\Repository;

use App\Modules\Reports\Production\Model\ProductionModelCollection;

class DoctrineProductionRepository implements ProductionRepositoryInterface
{
    public function findFinishedByDate(\DateTimeInterface $start, \DateTimeInterface $end): ProductionModelCollection
    {
        // TODO: Implement findFinishedByDate() method.
    }

    public function findPendingByDate(\DateTimeInterface $start, \DateTimeInterface $end): ProductionModelCollection
    {
        // TODO: Implement findPendingByDate() method.
    }
}