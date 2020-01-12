<?php


namespace App\Service;


class DateTimeHelper
{
    public static function toString(?\DateTime $dt, $format = 'Y-m-d')
    {
        return $dt instanceof \DateTime ? $dt->format($format) : '';
    }
}