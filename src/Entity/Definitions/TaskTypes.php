<?php

namespace App\Entity\Definitions;

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

    public const TYPE_DEFAULT_SLUG_GLUING = 'dpt01';
    public const TYPE_DEFAULT_SLUG_CNC = 'dpt02';
    public const TYPE_DEFAULT_SLUG_GRINDING = 'dpt03';
    public const TYPE_DEFAULT_SLUG_VARNISHING = 'dpt04';
    public const TYPE_DEFAULT_SLUG_PACKAGING = 'dpt05';

    public const TYPE_CUSTOM_SLUG = 'custom_task';

    public static function getAll(): array
    {
        return [
            self::TYPE_DEFAULT => [
                [
                    'name' => 'Klejenie',
                    'slug' => self::TYPE_DEFAULT_SLUG_GLUING
                ],

                [
                    'name' => 'CNC',
                    'slug' => self::TYPE_DEFAULT_SLUG_CNC
                ],

                [
                    'name' => 'Szlifowanie',
                    'slug' => self::TYPE_DEFAULT_SLUG_GRINDING
                ],

                [
                    'name' => 'Lakierowanie',
                    'slug' => self::TYPE_DEFAULT_SLUG_VARNISHING
                ],

                [
                    'name' => 'Pakowanie',
                    'slug' => self::TYPE_DEFAULT_SLUG_PACKAGING
                ]
            ],
            self::TYPE_CUSTOM => [
                [
                    'name' => '',
                    'slug' => self::TYPE_CUSTOM_SLUG
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
        return self::getSlugs(self::TYPE_DEFAULT);
    }

    /**
     * @param string $slug
     * @return string|null
     */
    public static function getTaskTypeBySlug(string $slug): ?string
    {
        if (in_array($slug, self::getSlugs(self::TYPE_DEFAULT))) {
            return self::TYPE_DEFAULT;
        } else if (in_array($slug, self::getSlugs(self::TYPE_CUSTOM))) {
            return self::TYPE_CUSTOM;
        }
        return null;
    }
}