<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Production;
use App\Entity\User;
use App\Exceptions\Production\ProductionAlreadyExistsException;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionControllerTest extends ApiTestCase
{
    /** @var User */
    private $user;
    /** @var Product */
    private $product;
    /** @var Agreement */
    private $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $factory = new EntityFactory($this->getManager());
        $customer = $factory->make(Customer::class);

        $this->user = $factory->make(User::class);
        $this->user->setRoles(['ROLE_ADMIN']);

        $this->product = $factory->make(Product::class);

        $this->agreement = $factory->make(
            Agreement::class,
            function(Agreement $agreement) use ($customer) {
                $agreement->setCustomer($customer);
            }
        );
        $this->getManager()->flush();
    }

    public function testShouldCreateProductionTasksWithProperDates()
    {
        $manager = $this->getManager();
        $dateStart = new \DateTime();
        $deadline = (clone $dateStart)->modify('+15 days');
        $expectedDateStart = (clone $deadline)->modify('-7 days')->setTime(7, 0);
        // Given
        $agreementLine = (new AgreementLine())
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setConfirmedDate($deadline)
            ->setAgreement($this->agreement)
            ->setProduct($this->product)
            ->setArchived(false)
            ->setDeleted(false);

        $manager->persist($agreementLine);
        $manager->flush();

        $client = $this->login($this->user);

        // When
        $client->xmlHttpRequest('POST', '/production/start/' . $agreementLine->getId());

        // Then
        /** @var Production[] $productionCollection */
        $productionCollection =
            $manager->getRepository(Production::class)
            ->findBy(['agreementLine' => $agreementLine]);

        $this->assertCount(5, $productionCollection);
        // dpt01
        $this->assertEquals($expectedDateStart, $productionCollection[0]->getDateStart());
        $this->assertEquals($deadline, $productionCollection[0]->getDateEnd());
        // dpt02
        $this->assertNull($productionCollection[1]->getDateStart());
        $this->assertEquals($deadline, $productionCollection[1]->getDateEnd());
        // dpt03
        $this->assertNull($productionCollection[2]->getDateStart());
        $this->assertEquals($deadline, $productionCollection[2]->getDateEnd());
        // dpt04
        $this->assertNull($productionCollection[3]->getDateStart());
        $this->assertEquals($deadline, $productionCollection[3]->getDateEnd());
        // dpt05
        $this->assertNull($productionCollection[4]->getDateStart());
        $this->assertEquals($deadline, $productionCollection[4]->getDateEnd());
    }

    public function testShouldThrowProductionAlreadyExistsExceptionIfProductionExist()
    {
        // Given
        $manager = $this->getManager();
        $agreementLine = (new AgreementLine())
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setConfirmedDate(new \DateTime())
            ->setAgreement($this->agreement)
            ->setProduct($this->product)
            ->setArchived(false)
            ->setDeleted(false);
        $manager->persist($agreementLine);

        $producton = new Production();
        $producton->setDepartmentSlug('dpt01');
        $producton->setAgreementLine($agreementLine);
        $producton->setStatus(0);

        $manager->persist($producton);
        $manager->flush();

        $client = $this->login($this->user);

        // When & Then
        $client->xmlHttpRequest('POST', '/production/start/' . $agreementLine->getId());

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }
}