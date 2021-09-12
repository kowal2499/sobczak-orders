<?php

namespace App\Tests\Reports\Production\Unit;

use App\Modules\Reports\Production\Model\ProductionModel;
use App\Modules\Reports\Production\Model\ProductionModelCollection;
use PHPUnit\Framework\TestCase;

class ProductionModelCollectionTest extends TestCase
{
    /** @var ProductionModel */
    private $collectionUnderTest;

    protected function setUp(): void
    {
        $this->collectionUnderTest = new ProductionModelCollection();
    }

    public function testShouldReturnZeroOnCountEmptyCollection()
    {
        // Given && When && Then
        $this->assertEquals(0, $this->collectionUnderTest->count());
    }

    public function testShouldAddProductionModelIntoCollection()
    {
        // Given
        $production = new ProductionModel();
        // When
        $this->collectionUnderTest->add($production);
        // Then
        $this->assertEquals(1, $this->collectionUnderTest->count());
    }

    public function testShouldIterateOnCollection()
    {
        // Given
        $production = new ProductionModel();
        // When
        $this->collectionUnderTest->add($production);
        // Then
        $this->assertTrue($this->collectionUnderTest->valid());
        $this->assertSame($production, $this->collectionUnderTest->current());
        $this->assertEquals(0, $this->collectionUnderTest->key());

        $this->collectionUnderTest->next();
        $this->assertFalse($this->collectionUnderTest->valid());
        $this->assertEquals(1, $this->collectionUnderTest->key());

        $this->collectionUnderTest->rewind();
        $this->assertTrue($this->collectionUnderTest->valid());
        $this->assertSame($production, $this->collectionUnderTest->current());
        $this->assertEquals(0, $this->collectionUnderTest->key());
    }


}