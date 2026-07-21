<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Repository\AgreementRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementInternalNumberTest extends ApiTestCase
{
    private AgreementRepository $agreementRepository;
    private AgreementLineRMRepository $agreementLineRMRepository;
    private EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->agreementRepository = $this->get(AgreementRepository::class);
        $this->agreementLineRMRepository = $this->get(AgreementLineRMRepository::class);
        $this->factory = new EntityFactory($this->getManager());
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateLinesWithDistinctInternalNumbers(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'orderNumber' => 'MULTI-1',
            'products' => [
                [
                    'productId' => $product01->getId(),
                    'requiredDate' => '2024-12-31',
                    'factor' => 0.5,
                    'internalNumber' => 'stairs-i',
                ],
                [
                    'productId' => $product02->getId(),
                    'requiredDate' => '2024-12-30',
                    'factor' => 0.5,
                    'internalNumber' => 'stairs-l',
                ],
            ],
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $order = $this->agreementRepository->findOneBy(['orderNumber' => 'MULTI-1']);
        $this->assertInstanceOf(Agreement::class, $order);

        $lines = $order->getAgreementLines();
        $this->assertCount(2, $lines);

        $this->assertEquals('stairs-i', $lines[0]->getInternalNumber());
        $this->assertEquals('stairs-l', $lines[1]->getInternalNumber());
        $this->assertEquals('MULTI-1-stairs-i', $lines[0]->getDisplayNumber());
        $this->assertEquals('MULTI-1-stairs-l', $lines[1]->getDisplayNumber());

        // Read model carries the suffix
        $rm0 = $this->agreementLineRMRepository->find($lines[0]->getId());
        $rm1 = $this->agreementLineRMRepository->find($lines[1]->getId());
        $this->assertEquals('stairs-i', $rm0->getInternalNumber());
        $this->assertEquals('stairs-l', $rm1->getInternalNumber());
    }

    public function testShouldRejectDuplicateInternalNumbersOnCreate(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When — two lines share the same internal number
        $client->request('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'orderNumber' => 'DUP-1',
            'products' => [
                [
                    'productId' => $product01->getId(),
                    'requiredDate' => '2024-12-31',
                    'factor' => 0.5,
                    'internalNumber' => 'same',
                ],
                [
                    'productId' => $product02->getId(),
                    'requiredDate' => '2024-12-30',
                    'factor' => 0.5,
                    'internalNumber' => 'same',
                ],
            ],
        ]);

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $this->assertNull($this->agreementRepository->findOneBy(['orderNumber' => 'DUP-1']));
    }

    public function testEmptyInternalNumbersDoNotCollide(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When — both lines have empty internal numbers
        $client->request('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'orderNumber' => 'EMPTY-1',
            'products' => [
                [
                    'productId' => $product01->getId(),
                    'requiredDate' => '2024-12-31',
                    'factor' => 0.5,
                    'internalNumber' => '',
                ],
                [
                    'productId' => $product02->getId(),
                    'requiredDate' => '2024-12-30',
                    'factor' => 0.5,
                    'internalNumber' => null,
                ],
            ],
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $order = $this->agreementRepository->findOneBy(['orderNumber' => 'EMPTY-1']);
        $this->assertInstanceOf(Agreement::class, $order);
        foreach ($order->getAgreementLines() as $line) {
            $this->assertNull($line->getInternalNumber());
            $this->assertEquals('EMPTY-1', $line->getDisplayNumber());
        }
    }

    public function testShouldRejectAddingLineWithDuplicateInternalNumberOnUpdate(): void
    {
        // Given — existing agreement with one line carrying an internal number
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();

        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($user)
            ->setOrderNumber('UPD-DUP');

        $line01 = new AgreementLine();
        $line01->setProduct($product01)
            ->setConfirmedDate(new \DateTime('2024-12-01'))
            ->setFactor(1.0)
            ->setInternalNumber('a')
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line01);

        $this->getManager()->persist($line01);
        $this->getManager()->persist($agreement);
        $this->getManager()->flush();

        $line01Id = $line01->getId();
        $agreementId = $agreement->getId();
        $this->getManager()->clear();

        // When — add a new line reusing the same internal number
        $client->request('POST', '/orders/patch/' . $agreementId, [
            'customerId' => $customer->getId(),
            'orderNumber' => 'UPD-DUP',
            'products' => [
                [
                    'id' => $line01Id,
                    'productId' => $product01->getId(),
                    'requiredDate' => '2024-12-01',
                    'factor' => 1.0,
                    'internalNumber' => 'a',
                ],
                [
                    'productId' => $product02->getId(),
                    'requiredDate' => '2024-12-02',
                    'factor' => 1.0,
                    'internalNumber' => 'a',
                ],
            ],
        ]);

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testShouldClearInternalNumberWhenSingleLineRemains(): void
    {
        // Given — agreement with two lines, both carrying suffixes
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();

        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($user)
            ->setOrderNumber('SHRINK-1');

        $line01 = new AgreementLine();
        $line01->setProduct($product01)
            ->setConfirmedDate(new \DateTime('2024-12-01'))
            ->setFactor(1.0)
            ->setInternalNumber('one')
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line01);

        $line02 = new AgreementLine();
        $line02->setProduct($product02)
            ->setConfirmedDate(new \DateTime('2024-12-02'))
            ->setFactor(1.0)
            ->setInternalNumber('two')
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line02);

        $this->getManager()->persist($line01);
        $this->getManager()->persist($line02);
        $this->getManager()->persist($agreement);
        $this->getManager()->flush();

        $line01Id = $line01->getId();
        $agreementId = $agreement->getId();
        $this->getManager()->clear();

        // When — remove line02, keeping only line01 (still carrying its suffix in payload)
        $client->request('POST', '/orders/patch/' . $agreementId, [
            'customerId' => $customer->getId(),
            'orderNumber' => 'SHRINK-1',
            'products' => [
                [
                    'id' => $line01Id,
                    'productId' => $product01->getId(),
                    'requiredDate' => '2024-12-01',
                    'factor' => 1.0,
                    'internalNumber' => 'one',
                ],
            ],
        ]);

        // Then — the single remaining line has its suffix cleared
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $line = $this->getManager()->find(AgreementLine::class, $line01Id);
        $this->assertNull($line->getInternalNumber());
        $this->assertEquals('SHRINK-1', $line->getDisplayNumber());
    }
}
