<?php

namespace App\Module\WorkConfiguration\Service;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Przesuwanie dat o dni robocze (z pominięciem weekendów, świąt i wyjątków z WorkSchedule).
 *
 * WorkScheduleService::getFreeDays() odpytuje repozytorium przy każdym wywołaniu, więc wołanie go
 * per rekord raportu dałoby N zapytań. Dni wolne są tu cache'owane per rok i ładowane leniwie —
 * w praktyce 1–2 zapytania na cały raport.
 */
class WorkingDayCalculator
{
    /** Bezpiecznik pętli — przesunięcie o X dni roboczych nigdy nie wymaga więcej niż tyle kroków. */
    private const MAX_STEPS_PER_DAY = 10;

    /** @var array<int, array<string, true>> dni wolne (Y-m-d) pogrupowane po roku */
    private array $freeDaysByYear = [];

    public function __construct(
        private readonly WorkScheduleService $workScheduleService,
    ) {
    }

    /**
     * Przesuwa datę o zadaną liczbę dni roboczych: dodatnia w przód, ujemna wstecz, zero zwraca
     * dzień wejściowy bez zmian (także gdy sam jest dniem wolnym).
     */
    public function shift(DateTimeInterface $date, int $workingDays): DateTimeImmutable
    {
        $current = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
        if (0 === $workingDays) {
            return $current;
        }

        $step = $workingDays > 0 ? '+1 day' : '-1 day';
        $remaining = abs($workingDays);
        $guard = $remaining * self::MAX_STEPS_PER_DAY;

        while ($remaining > 0 && $guard-- > 0) {
            $current = $current->modify($step);
            if (!$this->isFreeDay($current)) {
                --$remaining;
            }
        }

        return $current;
    }

    /**
     * Liczba dni roboczych w przedziale (from, to] — dzień początkowy nie jest liczony, końcowy tak.
     * Zwraca 0, gdy $to nie jest późniejsze niż $from.
     */
    public function countWorkingDays(DateTimeInterface $from, DateTimeInterface $to): int
    {
        $current = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $from->format('Y-m-d') . ' 00:00:00');
        $target = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $to->format('Y-m-d') . ' 00:00:00');

        $count = 0;
        while ($current < $target) {
            $current = $current->modify('+1 day');
            if (!$this->isFreeDay($current)) {
                ++$count;
            }
        }

        return $count;
    }

    public function isFreeDay(DateTimeInterface $date): bool
    {
        $year = (int) $date->format('Y');
        if (!isset($this->freeDaysByYear[$year])) {
            $this->freeDaysByYear[$year] = $this->loadFreeDays($year);
        }

        return isset($this->freeDaysByYear[$year][$date->format('Y-m-d')]);
    }

    /**
     * @return array<string, true>
     */
    private function loadFreeDays(int $year): array
    {
        $days = $this->workScheduleService->getFreeDays(
            new DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year)),
            new DateTimeImmutable(sprintf('%d-12-31 00:00:00', $year)),
        );

        $result = [];
        foreach ($days as $day) {
            /** @var WorkSchedule $day */
            $result[$day->getDate()->format('Y-m-d')] = true;
        }

        return $result;
    }
}
