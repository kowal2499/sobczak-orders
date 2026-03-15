<?php

namespace App\Entity\Definitions;

use App\Module\Production\ValueObject\DepartmentEnum;

class TaskTypes
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_CUSTOM = 'custom';

    public const TYPE_DEFAULT_STATUS_AWAITS = 0;
    public const TYPE_DEFAULT_STATUS_STARTED = 1;
    public const TYPE_DEFAULT_STATUS_PENDING = 2;
    public const TYPE_DEFAULT_STATUS_COMPLETED = 3;
    public const TYPE_DEFAULT_STATUS_NOT_APPLICABLE = 4;

    public const TYPE_CUSTOM_STATUS_AWAITS = 10;
    public const TYPE_CUSTOM_STATUS_PENDING = 11;
    public const TYPE_CUSTOM_STATUS_COMPLETED = 12;

    // Aliasy dla zgodności wstecznej
    public const TYPE_DEFAULT_SLUG_GLUING = 'dpt01';
    public const TYPE_DEFAULT_SLUG_CNC = 'dpt02';
    public const TYPE_DEFAULT_SLUG_GRINDING = 'dpt03';
    public const TYPE_DEFAULT_SLUG_VARNISHING = 'dpt04';
    public const TYPE_DEFAULT_SLUG_PACKAGING = 'dpt05';
    public const TYPE_DEFAULT_SLUG_INTOREX = 'dpt06';

    public const TYPE_CUSTOM_SLUG = 'custom_task';

    public static function getAll(): array
    {
        return [
            self::TYPE_DEFAULT => array_map(
                fn(DepartmentEnum $dept) => [
                    'name' => $dept->getName(),
                    'slug' => $dept->value,
                ],
                DepartmentEnum::getProductionDepartments()
            ),
            self::TYPE_CUSTOM => [
                [
                    'name' => '',
                    'slug' => DepartmentEnum::CUSTOM_TASK->value,
                ]
            ]
        ];
    }

    public static function getStatusesByTaskType(string $type): array
    {
        return ([
            self::TYPE_DEFAULT => [
                self::TYPE_DEFAULT_STATUS_AWAITS,
                self::TYPE_DEFAULT_STATUS_STARTED,
                self::TYPE_DEFAULT_STATUS_PENDING,
                self::TYPE_DEFAULT_STATUS_COMPLETED,
                self::TYPE_DEFAULT_STATUS_NOT_APPLICABLE
            ],
            self::TYPE_CUSTOM => [
                self::TYPE_CUSTOM_STATUS_AWAITS,
                self::TYPE_CUSTOM_STATUS_PENDING,
                self::TYPE_CUSTOM_STATUS_COMPLETED,
            ]
        ])[$type];
    }

    /**
     * @return string[]
     */
    public static function getSlugs(string $type): array
    {
        return array_map(function ($task) {
            return $task['slug'];
        }, self::getAll()[$type]);
    }

    public static function getDefaultSlugs(): array
    {
        return array_map(
            fn(DepartmentEnum $dept) => $dept->value,
            DepartmentEnum::getProductionDepartments()
        );
    }

    /**
     * @param string $slug
     * @return string|null
     */
    public static function getTaskTypeBySlug(string $slug): ?string
    {
        $dept = DepartmentEnum::tryFrom($slug);

        if ($dept === null) {
            return null;
        }

        return $dept->isProductionDepartment() ? self::TYPE_DEFAULT : self::TYPE_CUSTOM;
    }
}