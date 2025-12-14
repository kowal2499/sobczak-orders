<?php

namespace App\Module\Production\Factor\DTO;

use App\Module\Production\Entity\FactorSource;

class FactorDTO
{
    public FactorSource $source;
    public float        $value;
    public ?int         $agreementLineId;
    public ?string      $departmentSlug;
    public ?string      $description;
}