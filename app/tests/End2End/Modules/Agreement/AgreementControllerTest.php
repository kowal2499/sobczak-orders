<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Agreement\Service\AgreementLineTaggingPolicy;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Repository\FactorRepository;
use App\Module\Tag\Entity\TagDefinition;
use App\Module\Tag\Repository\TagAssignmentRepository;
use App\Repository\AgreementRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AgreementControllerTest extends ApiTestCase
{
    private AgreementRepository $agreementRepository;
    private AgreementLineRMRepository $agreementLineRMRepository;
    private TagAssignmentRepository $tagAssignmentRepository;
    private FactorRepository $factorRepository;
    private EntityFactory $factory;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->agreementRepository = $this->get(AgreementRepository::class);
        $this->agreementLineRMRepository = $this->get(AgreementLineRMRepository::class);
        $this->tagAssignmentRepository = $this->get(TagAssignmentRepository::class);
        $this->factorRepository = $this->get(FactorRepository::class);
        $this->factory = new EntityFactory($this->getManager());
        $this->tempFiles = [];

        // Create tag definition for capacity exceeded
        $tagDefinition = new TagDefinition(
            'Złożone pomimo przekroczenia mocy produkcyjnych',
            'agreement-line',
            'fa-warning',
            '#ff0000',
            AgreementLineTaggingPolicy::TAG_CAPACITY_EXCEEDED,
            false
        );
        $this->getManager()->persist($tagDefinition);
        $this->getManager()->flush();
    }

    protected function tearDown(): void
    {
        // Usuń utworzone pliki tymczasowe
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

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
                    'isCapacityExceeded' => true,
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

        // Verify read models were created
        $readModel01 = $this->agreementLineRMRepository->find($lines[0]->getId());
        $this->assertNotNull($readModel01);
        $this->assertEquals($lines[0]->getId(), $readModel01->getAgreementLineId());
        $this->assertEquals($order->getId(), $readModel01->getAgreementId());
        $this->assertEquals($customer->getId(), $readModel01->getCustomerId());
        $this->assertStringContainsString($customer->getName(), $readModel01->getCustomerName());
        $this->assertEquals('12123', $readModel01->getOrderNumber());
        $this->assertEquals($product01->getName(), $readModel01->getProductName());
        $this->assertEquals('some description 01', $readModel01->getDescription());
        $this->assertEquals(0.55, $readModel01->getFactor());
        $this->assertEquals('2024-12-31', $readModel01->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(AgreementLine::STATUS_WAITING, $readModel01->getStatus());
        $this->assertFalse($readModel01->isDeleted());
        $this->assertFalse($readModel01->isArchived());
        $this->assertFalse($readModel01->hasProduction());

        $readModel02 = $this->agreementLineRMRepository->find($lines[1]->getId());
        $this->assertNotNull($readModel02);
        $this->assertEquals($lines[1]->getId(), $readModel02->getAgreementLineId());
        $this->assertEquals($order->getId(), $readModel02->getAgreementId());
        $this->assertEquals($customer->getId(), $readModel02->getCustomerId());
        $this->assertStringContainsString($customer->getName(), $readModel02->getCustomerName());
        $this->assertEquals('12123', $readModel02->getOrderNumber());
        $this->assertEquals($product02->getName(), $readModel02->getProductName());
        $this->assertEquals('some description 02', $readModel02->getDescription());
        $this->assertEquals(0.15, $readModel02->getFactor());
        $this->assertEquals('2024-12-30', $readModel02->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(AgreementLine::STATUS_WAITING, $readModel02->getStatus());
        $this->assertFalse($readModel02->isDeleted());
        $this->assertFalse($readModel02->isArchived());
        $this->assertFalse($readModel02->hasProduction());

        // Verify tags - only first product should have capacity exceeded tag
        $this->getManager()->clear(); // Force reload from DB
        $tagsForLine1 = $this->tagAssignmentRepository->findBy(['contextId' => $lines[0]->getId()]);
        $this->assertCount(1, $tagsForLine1, 'First product should have 1 tag (capacity exceeded)');
        $this->assertEquals(
            AgreementLineTaggingPolicy::TAG_CAPACITY_EXCEEDED,
            $tagsForLine1[0]->getTagDefinition()->getSlug(),
            'First product should have capacity exceeded tag'
        );
        $this->assertEquals($user->getId(), $tagsForLine1[0]->getUser()->getId());

        $tagsForLine2 = $this->tagAssignmentRepository->findBy(['contextId' => $lines[1]->getId()]);
        $this->assertCount(0, $tagsForLine2, 'Second product should have no tags');

        // Verify factors - both products should have factors created
        $factorsForLine1 = $this->factorRepository->findBy(['agreementLine' => $lines[0]->getId()]);
        $this->assertCount(1, $factorsForLine1, 'First product should have 1 factor');
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $factorsForLine1[0]->getSource());
        $this->assertEquals(0.55, $factorsForLine1[0]->getFactorValue());

        $factorsForLine2 = $this->factorRepository->findBy(['agreementLine' => $lines[1]->getId()]);
        $this->assertCount(1, $factorsForLine2, 'Second product should have 1 factor');
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $factorsForLine2[0]->getSource());
        $this->assertEquals(0.15, $factorsForLine2[0]->getFactorValue());
    }

    public function testShouldUpdateAgreementWithModifiedAndDeletedLines(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product01 = $this->factory->make(Product::class);
        $product02 = $this->factory->make(Product::class);
        $product03 = $this->factory->make(Product::class);
        $this->getManager()->flush();

        // Utworzenie zamówienia z 3 liniami
        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($user)
            ->setOrderNumber('ORIGINAL-123');

        $line01 = new AgreementLine();
        $line01->setProduct($product01)
            ->setConfirmedDate(new \DateTime('2024-12-01'))
            ->setDescription('Line 1 - to be modified')
            ->setFactor(1.0)
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line01);

        $line02 = new AgreementLine();
        $line02->setProduct($product02)
            ->setConfirmedDate(new \DateTime('2024-12-02'))
            ->setDescription('Line 2 - unchanged')
            ->setFactor(2.0)
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line02);

        $line03 = new AgreementLine();
        $line03->setProduct($product03)
            ->setConfirmedDate(new \DateTime('2024-12-03'))
            ->setDescription('Line 3 - to be deleted')
            ->setFactor(3.0)
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line03);

        $this->getManager()->persist($line01);
        $this->getManager()->persist($line02);
        $this->getManager()->persist($line03);
        $this->getManager()->persist($agreement);

        // Dodanie 2 załączników do zamówienia
        $attachment01 = new Attachment();
        $attachment01->setAgreement($agreement);
        $attachment01->setName('attachment-01.pdf');
        $attachment01->setOriginalName('document-01.pdf');
        $attachment01->setExtension('pdf');
        $this->getManager()->persist($attachment01);

        $attachment02 = new Attachment();
        $attachment02->setAgreement($agreement);
        $attachment02->setName('attachment-02.pdf');
        $attachment02->setOriginalName('document-02.pdf');
        $attachment02->setExtension('pdf');
        $this->getManager()->persist($attachment02);

        $this->getManager()->flush();

        $line01Id = $line01->getId();
        $line02Id = $line02->getId();
        $line03Id = $line03->getId();
        $attachment01Id = $attachment01->getId();
        $attachment02Id = $attachment02->getId();

        $this->getManager()->clear();

        // When - aktualizacja: zmiana line01, pozostawienie line02, usunięcie line03, usunięcie attachment01
        $client->request('POST', '/orders/patch/' . $agreement->getId(), [
            'customerId' => $customer->getId(),
            'orderNumber' => 'UPDATED-456',
            'products' => [
                [
                    'id' => $line01Id,
                    'productId' => $product01->getId(),
                    'description' => 'Line 1 - MODIFIED',
                    'requiredDate' => '2024-12-15',
                    'factor' => 1.5,
                    'isCapacityExceeded' => false,
                ],
                [
                    'id' => $line02Id,
                    'productId' => $product02->getId(),
                    'description' => 'Line 2 - unchanged',
                    'requiredDate' => '2024-12-02',
                    'factor' => 2.0,
                    'isCapacityExceeded' => false,
                ]
                // line03 nie jest wysłane - zostanie usunięte
            ],
            'removedAttachmentIds' => json_encode([$attachment01Id]),
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Verify in database
        $this->getManager()->clear();
        $updatedAgreement = $this->agreementRepository->find($agreement->getId());

        $this->assertInstanceOf(Agreement::class, $updatedAgreement);
        $this->assertEquals('UPDATED-456', $updatedAgreement->getOrderNumber());
        $this->assertEquals($customer->getId(), $updatedAgreement->getCustomer()->getId());

        // Verify agreement lines - should have only 2 lines now
        $lines = $updatedAgreement->getAgreementLines();
        $this->assertCount(2, $lines);

        // Find lines by ID
        $updatedLine01 = null;
        $updatedLine02 = null;
        foreach ($lines as $line) {
            if ($line->getId() === $line01Id) {
                $updatedLine01 = $line;
            } elseif ($line->getId() === $line02Id) {
                $updatedLine02 = $line;
            }
        }

        // Verify line01 was modified
        $this->assertNotNull($updatedLine01);
        $this->assertEquals('Line 1 - MODIFIED', $updatedLine01->getDescription());
        $this->assertEquals('2024-12-15', $updatedLine01->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(1.5, $updatedLine01->getFactor());

        // Verify line02 was unchanged
        $this->assertNotNull($updatedLine02);
        $this->assertEquals('Line 2 - unchanged', $updatedLine02->getDescription());
        $this->assertEquals('2024-12-02', $updatedLine02->getConfirmedDate()->format('Y-m-d'));
        $this->assertEquals(2.0, $updatedLine02->getFactor());

        // Verify line03 was deleted from database
        $deletedLine = $this->getManager()->find(AgreementLine::class, $line03Id);
        $this->assertNull($deletedLine, 'Line 3 should be deleted from database');

        // Verify attachments - attachment01 should be deleted, attachment02 should remain
        $attachments = $updatedAgreement->getAttachments();
        $this->assertCount(1, $attachments, 'Agreement should have only 1 attachment after deletion');
        $this->assertEquals($attachment02Id, $attachments[0]->getId(), 'Remaining attachment should be attachment02');
        $this->assertEquals('attachment-02.pdf', $attachments[0]->getName());

        // Verify attachment01 was deleted from database
        $deletedAttachment = $this->getManager()->find(Attachment::class, $attachment01Id);
        $this->assertNull($deletedAttachment, 'Attachment01 should be deleted from database');

        // Verify read models
        $readModel01 = $this->agreementLineRMRepository->find($line01Id);
        $this->assertNotNull($readModel01);
        $this->assertEquals('UPDATED-456', $readModel01->getOrderNumber());
        $this->assertEquals('Line 1 - MODIFIED', $readModel01->getDescription());
        $this->assertEquals(1.5, $readModel01->getFactor());
        $this->assertEquals('2024-12-15', $readModel01->getConfirmedDate()->format('Y-m-d'));

        $readModel02 = $this->agreementLineRMRepository->find($line02Id);
        $this->assertNotNull($readModel02);
        $this->assertEquals('UPDATED-456', $readModel02->getOrderNumber());
        $this->assertEquals('Line 2 - unchanged', $readModel02->getDescription());
        $this->assertEquals(2.0, $readModel02->getFactor());

        // Read model for line03 should not exist (or be marked as deleted by event handler)
        $readModel03 = $this->agreementLineRMRepository->find($line03Id);
        $this->assertNull($readModel03, 'Read model for deleted line should not exist');

        // Verify factors were updated
        $factorsForLine1 = $this->factorRepository->findBy(['agreementLine' => $line01Id]);
        $this->assertCount(1, $factorsForLine1);
        $this->assertEquals(1.5, $factorsForLine1[0]->getFactorValue());

        $factorsForLine2 = $this->factorRepository->findBy(['agreementLine' => $line02Id]);
        $this->assertCount(1, $factorsForLine2);
        $this->assertEquals(2.0, $factorsForLine2[0]->getFactorValue());
    }

    private function createTestFile(string $filename, string $mimeType): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_attachment_');
        file_put_contents($tmpFile, 'test file content');

        // Śledź plik do późniejszego usunięcia
        $this->tempFiles[] = $tmpFile;

        return new UploadedFile(
            $tmpFile,
            $filename,
            $mimeType,
            null,
            true // test mode - don't check if file was actually uploaded
        );
    }
}
