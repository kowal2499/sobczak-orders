<?php

namespace App\Tests\End2End\Modules\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductControllerTest extends ApiTestCase
{
    private EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldListProductsMatchingSearchTerm(): void
    {
        // Given
        $term = uniqid('stairs-');
        $this->factory->make(Product::class, ['name' => "$term oak", 'factor' => 0.5]);
        $this->factory->make(Product::class, ['name' => "$term ash", 'factor' => 1.25]);
        $this->factory->make(Product::class, ['name' => 'unrelated']);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTS']));

        // When
        $client->xmlHttpRequest('GET', '/api/products', ['q' => $term]);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['page']);
        $this->assertEqualsCanonicalizing(["$term oak", "$term ash"], array_column($data['items'], 'name'));
        $this->assertEqualsCanonicalizing([0.5, 1.25], array_column($data['items'], 'factor'));
    }

    public function testShouldCreateProduct(): void
    {
        // Given
        $name = uniqid('new-product-');
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTS']));

        // When
        $client->xmlHttpRequest('POST', '/api/products', [], [], [], json_encode([
            'name' => $name,
            'factor' => 1.5,
            'description' => 'Schody dębowe',
        ]));

        // Then
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $product = $this->get(ProductRepository::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($product);
        $this->assertSame(1.5, $product->getFactor());
        $this->assertSame('Schody dębowe', $product->getDescription());
        $this->assertNotNull($product->getCreateDate());
    }

    public function testShouldUpdateProduct(): void
    {
        // Given
        $product = $this->factory->make(Product::class, ['name' => 'before', 'factor' => 0.3, 'description' => 'old']);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTS']));

        // When
        $client->xmlHttpRequest('PUT', '/api/products/' . $product->getId(), [], [], [], json_encode([
            'name' => 'after',
            'factor' => 2,
            'description' => '',
        ]));

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $product = $this->get(ProductRepository::class)->find($product->getId());
        $this->assertSame('after', $product->getName());
        $this->assertSame(2.0, $product->getFactor());
        $this->assertNull($product->getDescription());
    }

    public function testShouldReturnSingleProduct(): void
    {
        // Given
        $product = $this->factory->make(Product::class, ['name' => 'single', 'factor' => 0.75]);
        $this->getManager()->flush();
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTS']));

        // When
        $client->xmlHttpRequest('GET', '/api/products/' . $product->getId());

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['id' => $product->getId(), 'name' => 'single', 'description' => $product->getDescription(), 'factor' => 0.75], $data);
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testShouldRejectInvalidProduct(array $payload, string $field): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTS']));

        // When
        $client->xmlHttpRequest('POST', '/api/products', [], [], [], json_encode($payload));

        // Then
        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame($field, json_decode($client->getResponse()->getContent(), true)['field']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'blank name' => [['name' => '  ', 'factor' => 1], 'name'];
        yield 'missing factor' => [['name' => 'x'], 'factor'];
        yield 'negative factor' => [['name' => 'x', 'factor' => -0.1], 'factor'];
    }

    public function testShouldDenyAccessWithoutProductsRole(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], [], ['ROLE_CUSTOMER']));

        // When
        $client->xmlHttpRequest('GET', '/api/products');

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
