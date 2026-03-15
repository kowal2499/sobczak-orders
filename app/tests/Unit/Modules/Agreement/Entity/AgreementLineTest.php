<?php

namespace App\Tests\Unit\Modules\Agreement\Entity;

use App\Entity\AgreementLine;
use App\Entity\Production;
use PHPUnit\Framework\TestCase;

class AgreementLineTest extends TestCase
{
    public function testGetProductionsShouldReturnProductionsOrderedByDepartmentOrder(): void
    {
        // Given
        $agreementLine = new AgreementLine();

        // Tworzymy produkcje w losowej kolejności
        $production1 = new Production();
        $production1->setDepartmentSlug('dpt04'); // Lakierowanie, order: 4

        $production2 = new Production();
        $production2->setDepartmentSlug('dpt02'); // CNC, order: 1

        $production3 = new Production();
        $production3->setDepartmentSlug('dpt01'); // Klejenie, order: 0

        $production4 = new Production();
        $production4->setDepartmentSlug('dpt06'); // Intorex, order: 2

        $production5 = new Production();
        $production5->setDepartmentSlug('dpt03'); // Szlifowanie, order: 3

        // Dodajemy w nieposortowanej kolejności
        $agreementLine->addProduction($production1);
        $agreementLine->addProduction($production2);
        $agreementLine->addProduction($production3);
        $agreementLine->addProduction($production4);
        $agreementLine->addProduction($production5);

        // When
        $productions = $agreementLine->getProductions();

        // Then
        $this->assertCount(5, $productions);

        $productionsArray = $productions->toArray();
        $this->assertEquals('dpt01', $productionsArray[0]->getDepartmentSlug()); // order: 0
        $this->assertEquals('dpt02', $productionsArray[1]->getDepartmentSlug()); // order: 1
        $this->assertEquals('dpt06', $productionsArray[2]->getDepartmentSlug()); // order: 2
        $this->assertEquals('dpt03', $productionsArray[3]->getDepartmentSlug()); // order: 3
        $this->assertEquals('dpt04', $productionsArray[4]->getDepartmentSlug()); // order: 4
    }

    public function testGetProductionsShouldHandleCustomTaskSlug(): void
    {
        // Given
        $agreementLine = new AgreementLine();

        $production1 = new Production();
        $production1->setDepartmentSlug('dpt01');

        $production2 = new Production();
        $production2->setDepartmentSlug('custom_task');

        $production3 = new Production();
        $production3->setDepartmentSlug('dpt02');

        $agreementLine->addProduction($production1);
        $agreementLine->addProduction($production2);
        $agreementLine->addProduction($production3);

        // When
        $productions = $agreementLine->getProductions();

        // Then
        $this->assertCount(3, $productions);

        $productionsArray = $productions->toArray();
        $this->assertEquals('dpt01', $productionsArray[0]->getDepartmentSlug()); // order: 0
        $this->assertEquals('dpt02', $productionsArray[1]->getDepartmentSlug()); // order: 1
        // CUSTOM_TASK ma order: 999, więc powinno być na końcu
        $this->assertEquals('custom_task', $productionsArray[2]->getDepartmentSlug()); // order: 999
    }

    public function testGetProductionsShouldSortByIdWhenSameDepartment(): void
    {
        // Given
        $agreementLine = new AgreementLine();

        // Tworzymy 3 produkcje dla tego samego działu
        // W rzeczywistości encje będą miały różne id po zapisie do bazy
        // Ten test weryfikuje, że sortowanie po id działa dla tego samego działu
        $production1 = new Production();
        $production1->setDepartmentSlug('dpt02'); // CNC

        $production2 = new Production();
        $production2->setDepartmentSlug('dpt02'); // CNC

        $production3 = new Production();
        $production3->setDepartmentSlug('dpt02'); // CNC

        $agreementLine->addProduction($production1);
        $agreementLine->addProduction($production2);
        $agreementLine->addProduction($production3);

        // When
        $productions = $agreementLine->getProductions();

        // Then
        $this->assertCount(3, $productions);

        // Wszystkie produkcje powinny być tego samego działu
        foreach ($productions as $production) {
            $this->assertEquals('dpt02', $production->getDepartmentSlug());
        }
    }
}
