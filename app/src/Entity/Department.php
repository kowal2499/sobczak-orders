<?php

namespace App\Entity;

use App\Module\Production\ValueObject\DepartmentEnum;

/**
 * @deprecated Use DepartmentEnum from App\Module\Production\ValueObject instead
 * This class is kept for backward compatibility only
 */
class Department
{
    /** @deprecated Use DepartmentEnum::GLUING->value */
    const DPT01 = 'dpt01';

    /** @deprecated Use DepartmentEnum::CNC->value */
    const DPT02 = 'dpt02';

    /** @deprecated Use DepartmentEnum::GRINDING->value */
    const DPT03 = 'dpt03';

    /** @deprecated Use DepartmentEnum::VARNISHING->value */
    const DPT04 = 'dpt04';

    /** @deprecated Use DepartmentEnum::PACKAGING->value */
    const DPT05 = 'dpt05';

    /** @deprecated Use DepartmentEnum::INTOREX->value */
    const DPT06 = 'dpt06';

    /**
     * @deprecated Use DepartmentEnum::getProductionDepartments() and map to array
     * Zwraca wszystkie działy produkcyjne z nazwami i kolejnością
     */
    public static function names(): array
    {
        return array_map(
            fn(DepartmentEnum $dept) => [
                'name' => $dept->getName(),
                'slug' => $dept->value,
                'order' => $dept->getOrder(),
            ],
            DepartmentEnum::getProductionDepartments()
        );
    }

    /**
     * @deprecated Use array_map(fn($d) => $d->value, DepartmentEnum::getProductionDepartments())
     * Zwraca wszystkie slugi działów produkcyjnych
     */
    public static function getSlugs(): array
    {
        return array_map(
            fn(DepartmentEnum $dept) => $dept->value,
            DepartmentEnum::getProductionDepartments()
        );
    }

    /**
     * @deprecated Use DepartmentEnum::tryFrom($slug) instead
     * Zwraca informacje o dziale po slugu
     */
    public static function getDepartmentBySlug(string $slug): ?array
    {
        $dept = DepartmentEnum::tryFrom($slug);

        if ($dept === null || !$dept->isProductionDepartment()) {
            return null;
        }

        return [
            'name' => $dept->getName(),
            'slug' => $dept->value,
            'order' => $dept->getOrder(),
        ];
    }
}

