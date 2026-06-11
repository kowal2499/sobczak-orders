<?php

namespace App\Module\Reports\Production\Metric;

/**
 * Strategia pojedynczego miernika dashboardu. Każdy miernik czerpie dane z read modelu
 * AgreementLineRM i zwraca gotowy, serializowalny wynik (tablica DTO lub agregat).
 */
interface MetricStrategyInterface
{
    /**
     * Stabilna nazwa miernika, używana do wyboru strategii przez kontroler/provider.
     */
    public function getName(): string;

    /**
     * Mierniki agregatowe (capacity/bonus/orders_*) wymagają obu dat. Mierniki szczegółów
     * (orders_*_details) mogą działać z pominiętą dolną granicą — stąd daty są nullowalne.
     *
     * @return array<int|string, mixed> wynik miernika gotowy do serializacji JSON
     */
    public function compute(?\DateTimeInterface $start, ?\DateTimeInterface $end, bool $includeGhost = false): array;
}
