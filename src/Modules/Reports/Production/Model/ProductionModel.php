<?php

namespace App\Modules\Reports\Production\Model;

class ProductionModel
{
    /** @var int */
    private $productionId;

    /** @var bool */
    private $isFinished;

    /** @var float */
    private $factor;

    /** @var string */
    private $productName;

    /** @var string */
    private $customerName;

    /** @var \DateTimeInterface */
    private $productionStartDate;

    /** @var \DateTimeInterface */
    private $productionEndDate;

    public function __construct()
    {
    }
}