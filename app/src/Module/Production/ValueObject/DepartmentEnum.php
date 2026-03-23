<?php

namespace App\Module\Production\ValueObject;

enum DepartmentEnum: string
{
    case GLUING = 'dpt01';
    case CNC = 'dpt02';
    case GRINDING = 'dpt03';
    case VARNISHING = 'dpt04';
    case PACKAGING = 'dpt05';
    case INTOREX = 'dpt06';
    case CUSTOM_TASK = 'custom_task';

    /**
     * Zwraca tylko slug działu produkcyjnego (bez CUSTOM_TASK)
     */
    public static function getProductionDepartments(): array
    {
        $departments = array_filter(self::cases(), fn(self $dept) => $dept->isProductionDepartment());
        usort($departments, fn(self $a, self $b) => $a->getOrder() <=> $b->getOrder());
        return $departments;
    }

    /**
     * Zwraca nazwę działu
     */
    public function getName(): string
    {
        return match ($this) {
            self::GLUING => 'Klejenie',
            self::CNC => 'CNC',
            self::GRINDING => 'Szlifowanie',
            self::VARNISHING => 'Lakierowanie',
            self::PACKAGING => 'Pakowanie',
            self::INTOREX => 'Intorex',
            self::CUSTOM_TASK => 'Zadanie niestandardowe',
        };
    }

    /**
     * Zwraca kolejność działu (używane do sortowania)
     */
    public function getOrder(): int
    {
        return match ($this) {
            self::GLUING => 0,
            self::CNC => 1,
            self::INTOREX => 2,
            self::GRINDING => 3,
            self::VARNISHING => 4,
            self::PACKAGING => 5,
            self::CUSTOM_TASK => 999,
        };
    }

    /**
     * Czy jest działem produkcyjnym (nie custom task)
     */
    public function isProductionDepartment(): bool
    {
        return $this !== self::CUSTOM_TASK;
    }
}
