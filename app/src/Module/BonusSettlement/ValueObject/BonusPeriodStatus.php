<?php

namespace App\Module\BonusSettlement\ValueObject;

/**
 * Stan okresu rozliczeniowego. Zamknięty okres jest niezmienny - żeby cokolwiek w nim
 * poprawić, trzeba go najpierw otworzyć ponownie, co zostawia ślad w dzienniku aktywności.
 */
enum BonusPeriodStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
}
