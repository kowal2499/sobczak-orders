<?php

namespace App\Module\Production\ValueObject;

/**
 * Jedyne miejsce wiążące dział produkcyjny z grantem widoczności.
 */
final class DepartmentGrantMap
{
    /**
     * @return array<string, string> slug działu => grant widoczności, w kolejności działów
     */
    public static function all(): array
    {
        $map = [];
        foreach (DepartmentEnum::getProductionDepartments() as $department) {
            $grant = self::grantForDepartment($department);
            if ($grant === null) {
                continue;
            }
            $map[$department->value] = $grant;
        }

        return $map;
    }

    public static function grantFor(string $slug): ?string
    {
        $department = DepartmentEnum::tryFrom($slug);

        return $department === null ? null : self::grantForDepartment($department);
    }

    public static function grantForDepartment(DepartmentEnum $department): ?string
    {
        return match ($department) {
            DepartmentEnum::GLUING => 'production.show.gluing',
            DepartmentEnum::CNC => 'production.show.cnc',
            DepartmentEnum::GRINDING => 'production.show.grinding',
            DepartmentEnum::VARNISHING => 'production.show.laquering',
            DepartmentEnum::PACKAGING => 'production.show.packing',
            DepartmentEnum::INTOREX => 'production.show.intorex',
            DepartmentEnum::CUSTOM_TASK => null,
        };
    }
}
