<?php

namespace App\Tests\Unit\Modules\Production;

use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\DepartmentGrantMap;
use PHPUnit\Framework\TestCase;

class DepartmentGrantMapTest extends TestCase
{
    public function testShouldMapEveryProductionDepartment(): void
    {
        // When
        $map = DepartmentGrantMap::all();

        // Then
        $this->assertSame([
            'dpt01' => 'production.show.gluing',
            'dpt02' => 'production.show.cnc',
            'dpt06' => 'production.show.intorex',
            'dpt03' => 'production.show.grinding',
            'dpt04' => 'production.show.laquering',
            'dpt05' => 'production.show.packing',
        ], $map);
    }

    public function testShouldKeepDepartmentOrder(): void
    {
        // Given
        $expected = array_map(
            fn (DepartmentEnum $department) => $department->value,
            DepartmentEnum::getProductionDepartments()
        );

        // When
        $slugs = array_keys(DepartmentGrantMap::all());

        // Then
        $this->assertSame($expected, $slugs);
    }

    public function testShouldResolveGrantBySlug(): void
    {
        $this->assertSame('production.show.laquering', DepartmentGrantMap::grantFor('dpt04'));
    }

    public function testShouldReturnNullForUnknownSlug(): void
    {
        $this->assertNull(DepartmentGrantMap::grantFor('dpt99'));
    }

    public function testShouldReturnNullForCustomTask(): void
    {
        $this->assertNull(DepartmentGrantMap::grantForDepartment(DepartmentEnum::CUSTOM_TASK));
        $this->assertArrayNotHasKey('custom_task', DepartmentGrantMap::all());
    }
}
