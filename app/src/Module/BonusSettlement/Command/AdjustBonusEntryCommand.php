<?php

namespace App\Module\BonusSettlement\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ręczna korekta jednego wiersza rozliczenia.
 *
 * `factorsAdjusted === null` oznacza zdjęcie korekty, nie premię zerową - do odebrania premii
 * służy wartość 0 z notatką.
 */
class AdjustBonusEntryCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $entryId,
        #[Assert\PositiveOrZero(message: 'Korekta nie może być ujemna.')]
        public readonly ?float $factorsAdjusted = null,
        #[Assert\Length(max: 2000)]
        public readonly ?string $note = null,
    ) {
    }
}
