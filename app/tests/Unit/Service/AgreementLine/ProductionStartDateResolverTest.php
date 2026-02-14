<?php

namespace App\Tests\Unit\Service\AgreementLine;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Service\AgreementLine\ProductionStartDateResolverService;
use PHPUnit\Framework\TestCase;

class ProductionStartDateResolverTest extends TestCase
{
    /** @var ProductionStartDateResolverTest */
    private $serviceUnderTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceUnderTest = new ProductionStartDateResolverService();
    }

    public function testShouldGetNullWhenProductionCollectionIsEmpty()
    {
        // Given
        $agreementLine = new AgreementLine();

        // When
        $startDate = $this->serviceUnderTest->getStartDate($agreementLine->getProductions());

        // Then
        $this->assertNull($startDate);
    }

    public function testShouldGetEearliestDateOfProductionTasks()
    {
        // Given
        $agreementLine = new AgreementLine();
        $prod1 = new Production();
        $prod1->setCreatedAt(new \DateTime('2021-09-20 10:00:00'));
        $prod1->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);

        $prod2 = new Production();
        $prod2->setCreatedAt(new \DateTime('2021-09-20 09:59:59'));
        $prod2->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING);

        $prod3 = new Production();
        $prod3->setCreatedAt(new \DateTime('2021-09-20 08:59:59'));
        $prod3->setDepartmentSlug(TaskTypes::TYPE_CUSTOM_SLUG);

        $agreementLine->addProduction($prod1);
        $agreementLine->addProduction($prod2);
        $agreementLine->addProduction($prod3);

        // When
        $date = $this->serviceUnderTest->getStartDate($agreementLine->getProductions());

        // Then
        $this->assertEquals($prod2->getCreatedAt(), $date);
    }
}