<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Production;
use App\Entity\User;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionControllerTest extends ApiTestCase
{
    /** @var User */
    private $user;
    /** @var EntityFactory */
    private $factory;
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

    public function testSomething()
    {
        $manager = $this->getManager();
        // Given
        $agreementLine = (new AgreementLine())
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setConfirmedDate((new \DateTime())->modify('+30 days'))
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
        $productionCollection =
            $manager->getRepository(Production::class)
            ->findBy(['agreementLine' => $agreementLine]);

        $this->assertCount(5, $productionCollection);
    }
}