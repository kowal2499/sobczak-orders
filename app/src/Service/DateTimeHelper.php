<?php


namespace App\Service;


class DateTimeHelper
{
    public static function toString(?\DateTime $dt, $format = 'Y-m-d')
    {
        return $dt instanceof \DateTime ? $dt->format($format) : '';
    }

    public function today(): \DateTime
    {
        return (new \DateTime())->setTime(0, 0);
    }
}