<?php


namespace App\Service;


class NumberMultiplier
{
    public function isNumberPositive(int $number): bool
    {
        return $number > 0;
    }

}