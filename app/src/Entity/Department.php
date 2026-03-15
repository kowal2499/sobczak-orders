<?php

namespace App\Entity;

class Department
{
    const DPT01 = 'dpt01';
    const DPT02 = 'dpt02';
    const DPT03 = 'dpt03';
    const DPT04 = 'dpt04';
    const DPT05 = 'dpt05';
    const DPT06 = 'dpt06';

    public static function names() : array
    {
        return [
            [
                'name' => 'Klejenie',
                'slug' => self::DPT01,
                'order' => 0,
            ],

            [
                'name' => 'CNC',
                'slug' => self::DPT02,
                'order' => 1,
            ],

            [
                'name' => 'Intorex',
                'slug' => self::DPT06,
                'order' => 2,
            ],

            [
                'name' => 'Szlifowanie',
                'slug' => self::DPT03,
                'order' => 3,
            ],

            [
                'name' => 'Lakierowanie',
                'slug' => self::DPT04,
                'order' => 4,
            ],

            [
                'name' => 'Pakowanie',
                'slug' => self::DPT05,
                'order' => 5,
            ],

        ];
    }

    public static function getSlugs(): array
    {
        return array_map(function ($dpt) {
            return $dpt['slug'];
        }, self::names());
    }

    public static function getDepartmentBySlug(string $slug): ?array
    {
        foreach (self::names() as $dpt) {
            if ($dpt['slug'] === $slug) {
                return $dpt;
            }
        }
        return null;
    }
}