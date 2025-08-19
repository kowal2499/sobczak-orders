<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\AgreementRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementControllerTest extends ApiTestCase
{
    /** @var User $user */
    private $user;

    protected function setUp(): void
    {
        $this->factory = new EntityFactory($this->getManager());
        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_PRODUCTION']
        ]);
    }

    public function testShouldCreateAgreement(): void
    {
        // Given
        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();

        // When
        $client = $this->login($this->user);
        $client->xmlHttpRequest('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'products' => [
                [
                    'productId' => $product01->getId(),
                    'description' => 'some description 01',
                    'requiredDate' => '2024-12-31',
                    'factor' => 0.55
                ],
                [
                    'productId' => $product02->getId(),
                    'description' => 'some description 02',
                    'requiredDate' => '2024-12-30',
                    'factor' => 0.15
                ]
            ],
            'orderNumber' => 12123
        ]);
        $orderId = (json_decode($client->getResponse()->getContent()) ?? [null])[0];
        /** @var AgreementRepository $repository */
        $repository = $this->getManager()->getRepository(Agreement::class);


        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear(Agreement::class);
        $order = $repository->find($orderId);

        $this->assertInstanceOf(Agreement::class, $order);
        $this->assertEquals(12123, $order->getOrderNumber());
        $this->assertEquals($customer->getId(), $order->getCustomer()->getId());
        $this->assertEquals($this->user->getId(), $order->getUser()->getId());
        $this->assertNull($order->getStatus());

        $lines = $order->getAgreementLines();
        $this->assertCount(2, $lines);
        $this->assertEquals($product01->getId(), $lines[0]->getProduct()->getId());
        $this->assertEquals('some description 01', $lines[0]->getDescription());
        $this->assertEquals('2024-12-31', $lines[0]->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(0.55, $lines[0]->getFactor());
        $this->assertEquals(AgreementLine::STATUS_WAITING, $lines[0]->getStatus());
        $this->assertFalse($lines[0]->getArchived());
        $this->assertFalse($lines[0]->getDeleted());
        $this->assertEquals($product02->getId(), $lines[1]->getProduct()->getId());
        $this->assertEquals('some description 02', $lines[1]->getDescription());
        $this->assertEquals('2024-12-30', $lines[1]->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(0.15, $lines[1]->getFactor());
        $this->assertEquals(AgreementLine::STATUS_WAITING, $lines[1]->getStatus());
        $this->assertFalse($lines[1]->getArchived());
        $this->assertFalse($lines[1]->getDeleted());
    }
}