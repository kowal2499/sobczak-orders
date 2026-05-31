<?php

namespace App\Module\Reports\Schedule\DTO;

class ScheduleEventDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $resourceId,
        public readonly int $agreementLineId,
        public readonly string $orderName,
        public readonly string $orderStatus,
        public readonly string $eventType,
        public readonly string $dateStart,
        public readonly string $dateEnd,
        public readonly array $meta,
        public readonly ?string $color = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'resourceId' => $this->resourceId,
            'agreementLineId' => $this->agreementLineId,
            'orderName' => $this->orderName,
            'orderStatus' => $this->orderStatus,
            'eventType' => $this->eventType,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
            'meta' => $this->meta,
        ];
        if ($this->color !== null) {
            $data['color'] = $this->color;
        }
        return $data;
    }
}
