<?php

namespace App\Entity;

class Department
{
    public static function names() : array
    {
        return [
            [
                'name' => 'Klejenie',
                'slug' => 'dpt01'
            ],

            [
                'name' => 'CNC',
                'slug' => 'dpt02'
            ],

            [
                'name' => 'Szlifowanie',
                'slug' => 'dpt03'
            ],

            [
                'name' => 'Lakierowanie',
                'slug' => 'dpt04'
            ],

            [
                'name' => 'Pakowanie',
                'slug' => 'dpt05'
            ]

        ];
    }
}