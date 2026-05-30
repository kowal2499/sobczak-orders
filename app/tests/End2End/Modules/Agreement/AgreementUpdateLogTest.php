<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Repository\AgreementRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AgreementUpdateLogTest extends ApiTestCase
{
    private AgreementRepository $agreementRepository;
    private ActivityLogRepository $activityLogRepository;
    private EntityFactory $factory;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->agreementRepository = $this->get(AgreementRepository::class);
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
        $this->factory = new EntityFactory($this->getManager());
        $this->tempFiles = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldLogAgreementUpdatedWithAllChanges(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customerA = $this->factory->make(Customer::class);
        $customerB = $this->factory->make(Customer::class);
        $productOld = $this->factory->make(Product::class);
        $productNew = $this->factory->make(Product::class);
        $this->getManager()->flush();

        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customerA)
            ->setUser($user)
            ->setOrderNumber('ORIG-1');

        $line = new AgreementLine();
        $line->setProduct($productOld)
            ->setConfirmedDate(new \DateTime('2024-12-01'))
            ->setDescription('old desc')
            ->setFactor(1.0)
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line);

        $attachment = new Attachment();
        $attachment->setAgreement($agreement);
        $attachment->setName('stored-old.pdf');
        $attachment->setOriginalName('document-old.pdf');
        $attachment->setExtension('pdf');

        $this->getManager()->persist($line);
        $this->getManager()->persist($attachment);
        $this->getManager()->persist($agreement);
        $this->getManager()->flush();

        $agreementId = $agreement->getId();
        $lineId = $line->getId();
        $attachmentId = $attachment->getId();
        $customerBId = $customerB->getId();
        $productNewId = $productNew->getId();
        $productOldName = $productOld->getName();
        $productNewName = $productNew->getName();
        $customerAName = $customerA->getName();
        $customerBName = $customerB->getName();
        $this->getManager()->clear();

        $uploadedFile = $this->createTestFile('new-attachment.pdf', 'application/pdf');

        // When — change customer, order number, the line's product/factor/date/description,
        // remove the existing attachment and add a new one.
        $client->request('POST', '/orders/patch/' . $agreementId, [
            'customerId' => $customerBId,
            'orderNumber' => 'NEW-2',
            'products' => [
                [
                    'id' => $lineId,
                    'productId' => $productNewId,
                    'description' => 'new desc',
                    'requiredDate' => '2025-01-15',
                    'factor' => 2.5,
                    'isCapacityExceeded' => false,
                ],
            ],
            'removedAttachmentIds' => json_encode([$attachmentId]),
        ], [
            'file' => [$uploadedFile],
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $updatedLogs = $this->activityLogRepository->findBy(
            ['type' => AgreementActivityLogType::AGREEMENT_UPDATED->value],
            ['id' => 'ASC'],
        );
        $this->assertCount(1, $updatedLogs, 'Exactly one agreement.updated log expected');

        /** @var ActivityLog $log */
        $log = $updatedLogs[0];
        $this->assertEquals('activity_log.agreement.updated', $log->getContent());
        $this->assertEquals($user->getId(), $log->getUser()?->getId());
        $this->assertSame(['agreementId' => (string) $agreementId], $this->logFields($log));

        $changes = $log->getContentParams()['changes'] ?? null;
        $this->assertIsArray($changes);

        // Agreement-level changes
        $customerChange = $this->findChange($changes, 'agreement', 'customer');
        $this->assertSame($customerAName, $customerChange['old']);
        $this->assertSame($customerBName, $customerChange['new']);

        $orderNumberChange = $this->findChange($changes, 'agreement', 'orderNumber');
        $this->assertSame('ORIG-1', $orderNumberChange['old']);
        $this->assertSame('NEW-2', $orderNumberChange['new']);

        // Line-level changes — labelled by product name, carry the line id
        $productChange = $this->findChange($changes, 'line', 'product');
        $this->assertSame($lineId, $productChange['lineId']);
        $this->assertSame($productNewName, $productChange['productName']);
        $this->assertSame($productOldName, $productChange['old']);
        $this->assertSame($productNewName, $productChange['new']);

        $factorChange = $this->findChange($changes, 'line', 'factor');
        $this->assertSame('1', $factorChange['old']);
        $this->assertSame('2.5', $factorChange['new']);

        $dateChange = $this->findChange($changes, 'line', 'confirmedDate');
        $this->assertSame('2024-12-01', $dateChange['old']);
        $this->assertSame('2025-01-15', $dateChange['new']);

        $descriptionChange = $this->findChange($changes, 'line', 'description');
        $this->assertSame('old desc', $descriptionChange['old']);
        $this->assertSame('new desc', $descriptionChange['new']);

        // Attachment changes
        $removed = $this->findChange($changes, 'agreement', 'attachmentRemoved');
        $this->assertSame('document-old.pdf', $removed['value']);

        $added = $this->findChange($changes, 'agreement', 'attachmentAdded');
        $this->assertStringContainsString('new-attachment', $added['value']);
    }

    public function testShouldNotLogAgreementUpdatedWhenNothingChanged(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $product = $this->factory->make(Product::class);
        $this->getManager()->flush();

        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($user)
            ->setOrderNumber('SAME-1');

        $line = new AgreementLine();
        $line->setProduct($product)
            ->setConfirmedDate(new \DateTime('2024-12-01'))
            ->setDescription('unchanged')
            ->setFactor(1.0)
            ->setStatus(AgreementLine::STATUS_WAITING)
            ->setDeleted(false)
            ->setArchived(false);
        $agreement->addAgreementLine($line);

        $this->getManager()->persist($line);
        $this->getManager()->persist($agreement);
        $this->getManager()->flush();

        $agreementId = $agreement->getId();
        $lineId = $line->getId();
        $customerId = $customer->getId();
        $productId = $product->getId();
        $this->getManager()->clear();

        // When — patch with identical values
        $client->request('POST', '/orders/patch/' . $agreementId, [
            'customerId' => $customerId,
            'orderNumber' => 'SAME-1',
            'products' => [
                [
                    'id' => $lineId,
                    'productId' => $productId,
                    'description' => 'unchanged',
                    'requiredDate' => '2024-12-01',
                    'factor' => 1.0,
                    'isCapacityExceeded' => false,
                ],
            ],
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $updatedLogs = $this->activityLogRepository->findBy(
            ['type' => AgreementActivityLogType::AGREEMENT_UPDATED->value],
        );
        $this->assertCount(0, $updatedLogs, 'No agreement.updated log expected when nothing changed');
    }

    private function logFields(ActivityLog $log): array
    {
        $out = [];
        foreach ($log->getLogFields() as $field) {
            $out[$field->getName()] = $field->getValue();
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     * @return array<string, mixed>
     */
    private function findChange(array $changes, string $scope, string $field): array
    {
        foreach ($changes as $change) {
            if (($change['scope'] ?? null) === $scope && ($change['field'] ?? null) === $field) {
                return $change;
            }
        }

        $this->fail(sprintf('Change not found: scope=%s field=%s', $scope, $field));
    }

    private function createTestFile(string $filename, string $mimeType): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_attachment_');
        file_put_contents($tmpFile, 'test file content');
        $this->tempFiles[] = $tmpFile;

        return new UploadedFile($tmpFile, $filename, $mimeType, null, true);
    }
}
