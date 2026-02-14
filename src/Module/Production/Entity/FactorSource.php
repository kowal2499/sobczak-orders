<?php

namespace App\Module\Production\Entity;

enum FactorSource: string
{
    case AGREEMENT_LINE = 'agreement_line';
    case FACTOR_ADJUSTMENT_BONUS = 'factor_adjustment_bonus';
    case FACTOR_ADJUSTMENT_RATIO = 'factor_adjustment_ratio';
}
