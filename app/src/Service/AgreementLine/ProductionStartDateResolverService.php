<?php

namespace App\Service\AgreementLine;

use App\Entity\Definitions\TaskTypes;
use Doctrine\Common\Collections\Collection;

class ProductionStartDateResolverService
{
    public function getStartDate(Collection $productionTasks): ?\DateTimeInterface
    {
        if ($productionTasks->isEmpty()) {
            return null;
        }

        return $this->findEarliestDate($productionTasks);
    }

    private function findEarliestDate($productionTasks): \DateTimeInterface
    {
        $earliestStartDate = null;
        foreach ($productionTasks as $production) {
            if (false === in_array($production->getDepartmentSlug(), TaskTypes::getDefaultSlugs())) {
                continue;
            }
            if (is_null($earliestStartDate)) {
                $earliestStartDate = $production->getCreatedAt();
            }
            if ($earliestStartDate > $production->getCreatedAt()) {
                $earliestStartDate = $production->getCreatedAt();
            }
        }
        return $earliestStartDate;
    }
}