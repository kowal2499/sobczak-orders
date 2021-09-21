<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Product;
use App\Entity\Production;
use App\Entity\User;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionControllerTest extends ApiTestCase
{
    /** @var User */
    private $user;
    /** @var Product */
    private $product;
    /** @var Agreement */
    private $agreement;
    /** @var AgreementLineChainFactory */
    private $chainFactory;
    /** @var EntityFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EntityFactory($this->getManager());
        $customer = $this->factory->make(Customer::class);
        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);
        $this->product = $this->factory->make(Product::class);
        $this->agreement = $this->factory->make(Agreement::class, [
            'customer' => $customer
        ]);

        $this->chainFactory = new AgreementLineChainFactory($this->factory);
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

    public function testShouldSetAgreementLineStatusToStatusManufacturing()
    {
        // Given
        $agreementLine = $this->chainFactory->make([], []);
        $client = $this->login($this->user);
        // When & Then
        $client->xmlHttpRequest('POST', '/production/start/' . $agreementLine->getId());
        /** @var AgreementLine $agreementLineWithProduction */
        $agreementLineWithProduction = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());
        $this->assertEquals(AgreementLine::STATUS_MANUFACTURING, $agreementLineWithProduction->getStatus());
    }

    public function testShouldSetProductionStartDate()
    {
        // Given
        $agreementLine = $this->chainFactory->make([], []);
        $client = $this->login($this->user);
        // When
        $client->xmlHttpRequest('POST', '/production/start/' . $agreementLine->getId());
        // Then
        /** @var AgreementLine $agreementLineWithProduction */
        $agreementLineWithProduction = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());
        $createdAt = $agreementLineWithProduction->getProductions()[0]->getCreatedAt();
        $this->assertEquals($createdAt, $agreementLineWithProduction->getProductionStartDate());
    }

    public function testShouldSetProductionCompletionDateToNullOnDelete()
    {
        // Given
        $agreementLine = $this->chainFactory->make([], [
            'status' => AgreementLine::STATUS_MANUFACTURING,
            'productionStartDate' => new \DateTime('2021-09-01 12:00:00'),
            'productionCompletionDate' => new \DateTime('2021-09-15 10:00:00')
        ]);
        $client = $this->login($this->user);

        // When
        $client->xmlHttpRequest('POST', '/production/delete/' . $agreementLine->getId());

        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());

        // Then
        $this->assertNull($agreementLineAfter->getProductionCompletionDate());
    }

    public function testShouldSetProductionStartDateToNullOnDelete()
    {
        // Given
        $agreementLine = $this->chainFactory->make([], [
            'status' => AgreementLine::STATUS_MANUFACTURING,
            'productionStartDate' => new \DateTime('2021-09-01 12:00:00'),
            'productionCompletionDate' => new \DateTime('2021-09-15 10:00:00')
        ]);
        $client = $this->login($this->user);

        // When
        $client->xmlHttpRequest('POST', '/production/delete/' . $agreementLine->getId());

        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());

        // Then
        $this->assertNull($agreementLineAfter->getProductionStartDate());
    }

    public function testShouldSetAgreementLineStatusToWaitingOnDelete()
    {
        // Given
        $agreementLine = $this->chainFactory->make([], [
            'status' => AgreementLine::STATUS_MANUFACTURING,
            'productionStartDate' => new \DateTime('2021-09-01 12:00:00'),
            'productionCompletionDate' => new \DateTime('2021-09-15 10:00:00')
        ]);
        $client = $this->login($this->user);

        // When
        $client->xmlHttpRequest('POST', '/production/delete/' . $agreementLine->getId());

        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());

        // Then
        $this->assertEquals(AgreementLine::STATUS_WAITING, $agreementLineAfter->getStatus());
    }

    public function testShouldRemoveProductionTasksOnDelete()
    {
        // Given
        $agreementLine = (new AgreementLineFixtureHelpers($this->factory, $this->chainFactory))
            ->makeAgreementLineWithProductionTasks(['productionCompletionDate' => new \DateTime('2021-09-06')]);
        $client = $this->login($this->user);
        // When
        $client->xmlHttpRequest('POST', '/production/delete/' . $agreementLine->getId());

        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());

        // Then
        $this->assertEmpty($agreementLineAfter->getProductions());
    }
}