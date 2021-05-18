<?php

namespace App\DTO\Production;

class ProductionTaskDTO
{
    private $taskSlug;
    private $title;
    private $status;
    private $dateFrom;
    private $dateTo;

    /**
     * ProductionTaskDTO constructor.
     * @param $taskSlug
     * @param int $status
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     */
    public function __construct($taskSlug, string $title, int $status, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null)
    {
        $this->taskSlug = $taskSlug;
        $this->status = $status;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param mixed $dateFrom
     */
    public function setDateFrom($dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return mixed
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param mixed $dateTo
     */
    public function setDateTo($dateTo): void
    {
        $this->dateTo = $dateTo;
    }

    /**
     * @return mixed
     */
    public function getTaskSlug()
    {
        return $this->taskSlug;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param mixed $taskSlug
     */
    public function setTaskSlug($taskSlug): void
    {
        $this->taskSlug = $taskSlug;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}