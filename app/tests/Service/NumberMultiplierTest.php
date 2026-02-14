<?php


namespace App\Tests\Service;

use \App\Service\NumberMultiplier;
use PHPUnit\Framework\TestCase;

class NumberMultiplierTest extends TestCase
{
    public function testCreateNewNumberMultiplier(): void
    {
        $numberMultiplier = new NumberMultiplier();
        $this->assertInstanceOf(NumberMultiplier::class, $numberMultiplier);
    }

    public function testPositiveNumberIsPositive(): void
    {
        $numberMultiplier = new NumberMultiplier();
        $positiveNumber = $numberMultiplier->isNumberPositive(5);
        $this->assertTrue($positiveNumber);
    }

    public function testNegativeNumberIsNotPositive(): void
    {
        $numberMultiplier = new NumberMultiplier();
        $negativeNumber = $numberMultiplier->isNumberPositive(-1);
        $this->assertFalse($negativeNumber);
    }
}