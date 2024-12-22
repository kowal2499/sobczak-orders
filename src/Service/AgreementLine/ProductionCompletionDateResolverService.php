<?php

namespace App\Service\AgreementLine;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\Common\Collections\Collection;

class ProductionCompletionDateResolverService
{
    /**
     * @param Collection|Production[] $productionTasks
     * @return \DateTime|null
     */
    public function getCompletionDate(Collection $productionTasks): ?\DateTimeInterface
    {
        if ($productionTasks->count() === 0) {
            return null;
        }
        $isOnlyNA = true;
        $completionLogs = [];
        foreach ($productionTasks as $task) {
            if (false === in_array(
                $task->getStatus(),
                    [TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED]
                )
            ) {
                return null;
            }
            $isOnlyNA = $isOnlyNA && ($task->getStatus() == TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);
            $completionLogs = array_merge(
                $completionLogs,
                $this->filterByStatus($task->getStatusLogs()->toArray(), TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED)
            );
        }
        if ($isOnlyNA || empty($completionLogs)) {
            return null;
        }
        return $this->pickLatestDate($completionLogs);
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