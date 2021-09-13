<?php

namespace App\Service\AgreementLine;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\Common\Collections\Collection;

class ProductionCompletionDateResolverService
{
    /**
     * @param Collection $productionTasks
     * @return \DateTime|null
     */
    public function getCompletionDate(Collection $productionTasks): ?\DateTimeInterface
    {
        if (empty($productionTasks)) {
            return null;
        }

        foreach ($productionTasks as $task) {
            if (
                $task->getDepartmentSlug() === TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING
                && $task->getStatus() == TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED
            ) {
                $logs = $this->filterByStatus($task->getStatusLogs()->toArray(), TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

                if (empty($logs)) {
                    throw new \RuntimeException('No status logs of required type');
                }

                return $this->pickLatestDate($logs);
            }
        }
        return null;
    }

    /**
     * @param StatusLog[] $logs
     * @param string $status
     * @return array
     */
    private function filterByStatus(array $logs, string $status): array
    {
        return array_filter($logs, function (StatusLog $log) use ($status) {
            return $log->getCurrentStatus() === $status;
        });
    }

    /**
     * @param StatusLog[] $logs
     * @return \DateTime
     */
    private function pickLatestDate(array $logs): \DateTimeInterface
    {
        usort($logs, function (StatusLog $log1, StatusLog $log2) {
            if ($log1->getCreatedAt() == $log2->getCreatedAt()) {
                return 0;
            }
            return $log1->getCreatedAt() > $log2->getCreatedAt() ? -1 : 1;
        });

        return $logs[0]->getCreatedAt();
    }
}