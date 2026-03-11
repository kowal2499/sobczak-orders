<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Repository\AgreementRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AgreementControllerTest extends ApiTestCase
{
    private AgreementRepository $agreementRepository;
    private EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->agreementRepository = $this->get(AgreementRepository::class);
        $this->factory = new EntityFactory($this->getManager());
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateAgreementWithProductsAndAttachment(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $this->getManager()->flush();
        $this->getManager()->clear();

        $uploadedFile = $this->createTestFile('test-document.pdf', 'application/pdf');

        // When
        $client->request('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'products' => [
                [
                    'productId' => $product01->getId(),
                    'description' => 'some description 01',
                    'requiredDate' => '2024-12-31',
                    'factor' => 0.55,
                    'isCapacityExceeded' => false,
                ],
                [
                    'productId' => $product02->getId(),
                    'description' => 'some description 02',
                    'requiredDate' => '2024-12-30',
                    'factor' => 0.15,
                    'isCapacityExceeded' => false,
                ]
            ],
            'orderNumber' => '12123'
        ], [
            'file' => [$uploadedFile]
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Verify in database
        $this->getManager()->clear();
        $order = $this->agreementRepository->findOneBy(['orderNumber' => '12123']);

        $this->assertInstanceOf(Agreement::class, $order);
        $this->assertEquals('12123', $order->getOrderNumber());
        $this->assertEquals($customer->getId(), $order->getCustomer()->getId());
        $this->assertEquals($user->getId(), $order->getUser()->getId());
        $this->assertNull($order->getStatus());

        // Verify agreement lines
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

        // Verify attachment
        $attachments = $order->getAttachments();
        $this->assertCount(1, $attachments);

        /** @var Attachment $attachment */
        $attachment = $attachments[0];
        $this->assertStringContainsString('test-document', $attachment->getOriginalName());
        $this->assertEquals('pdf', $attachment->getExtension());
        $this->assertNotEmpty($attachment->getName());
    }

    private function createTestFile(string $filename, string $mimeType): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_attachment_');
        file_put_contents($tmpFile, 'test file content');

        return new UploadedFile(
            $tmpFile,
            $filename,
            $mimeType,
            null,
            true // test mode - don't check if file was actually uploaded
        );
    }
}
