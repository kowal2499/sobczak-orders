<?php

namespace App\Module\Agreement\Service;

/**
 * Określa reguły przypisywania tagów do AgreementLine na podstawie danych z formularza.
 *
 * Ta klasa enkapsuluje logikę biznesową decyzyjną dotyczącą tagowania,
 * dzięki czemu może być łatwo testowana jednostkowo bez zależności od infrastruktury.
 */
class AgreementLineTaggingPolicy
{
    public const TAG_CAPACITY_EXCEEDED = 'zlozone-pomimo-przekroczenia-mocy-produkcyjnych';

    /**
     * @param array $productData Dane produktu z CreateAgreementCommand::$products
     * @return string[] Lista slugów tagów do przypisania
     */
    public function getTagsForAgreementLine(array $productData): array
    {
        $tags = [];

        if ($this->isCapacityExceeded($productData)) {
            $tags[] = self::TAG_CAPACITY_EXCEEDED;
        }

        return $tags;
    }

    private function isCapacityExceeded(array $productData): bool
    {
        return (bool) ($productData['isCapacityExceeded'] ?? false);
    }
}
