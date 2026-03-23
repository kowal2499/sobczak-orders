<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Agreement;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class AttachmentControllerTest extends ApiTestCase
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

    public function testShouldDownloadAttachment(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $this->factory->flush();

        $agreement = new Agreement();
        $agreement->setCustomer($customer);
        $agreement->setOrderNumber('TEST-001');
        $agreement->setStatus(0);
        $agreement->setCreateDate(new \DateTime());
        $agreement->setUpdateDate(new \DateTime());
        $this->getManager()->persist($agreement);

        $attachment = new Attachment();
        $attachment->setAgreement($agreement);
        $attachment->setName('test-file-123.CS');
        $attachment->setOriginalName('TestDocument');
        $attachment->setExtension('CS');

        $this->getManager()->persist($attachment);
        $this->getManager()->flush();

        // Tworzenie testowego pliku
        $uploadsPath = $this->getContainer()->getParameter('kernel.project_dir') . '/public/uploads';
        $testFilePath = $uploadsPath . '/agreements/' . $attachment->getName();

        if (!is_dir(dirname($testFilePath))) {
            mkdir(dirname($testFilePath), 0777, true);
        }
        file_put_contents($testFilePath, 'Test file content');

        // When
        $client->request('GET', '/attachments/' . $attachment->getId() . '/download');

        // Then
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Sprawdzenie nagłówków
        $this->assertTrue($response->headers->has('Content-Disposition'));
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('TestDocument.CS', $contentDisposition);

        // Sprawdzenie, że plik istnieje
        $this->assertFileExists($testFilePath);

        // Cleanup
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }
    }

    public function testShouldReturn404WhenAttachmentNotFound(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        // When
        $client->request('GET', '/attachments/99999/download');

        // Then
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenFileNotFoundOnDisk(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $this->factory->flush();

        $agreement = new Agreement();
        $agreement->setCustomer($customer);
        $agreement->setOrderNumber('TEST-002');
        $agreement->setStatus(0);
        $agreement->setCreateDate(new \DateTime());
        $agreement->setUpdateDate(new \DateTime());
        $this->getManager()->persist($agreement);

        $attachment = new Attachment();
        $attachment->setAgreement($agreement);
        $attachment->setName('non-existent-file.pdf');
        $attachment->setOriginalName('NonExistent');
        $attachment->setExtension('pdf');

        $this->getManager()->persist($attachment);
        $this->getManager()->flush();

        // When
        $client->request('GET', '/attachments/' . $attachment->getId() . '/download');

        // Then
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldViewAttachmentInline(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $customer = $this->factory->make(Customer::class);
        $this->factory->flush();

        $agreement = new Agreement();
        $agreement->setCustomer($customer);
        $agreement->setOrderNumber('TEST-003');
        $agreement->setStatus(0);
        $agreement->setCreateDate(new \DateTime());
        $agreement->setUpdateDate(new \DateTime());
        $this->getManager()->persist($agreement);

        $attachment = new Attachment();
        $attachment->setAgreement($agreement);
        $attachment->setName('image-test.jpg');
        $attachment->setOriginalName('TestImage');
        $attachment->setExtension('jpg');

        $this->getManager()->persist($attachment);
        $this->getManager()->flush();

        // Tworzenie testowego pliku
        $uploadsPath = $this->getContainer()->getParameter('kernel.project_dir') . '/public/uploads';
        $testFilePath = $uploadsPath . '/agreements/' . $attachment->getName();

        if (!is_dir(dirname($testFilePath))) {
            mkdir(dirname($testFilePath), 0777, true);
        }
        file_put_contents($testFilePath, 'Fake image content');

        // When
        $client->request('GET', '/attachments/' . $attachment->getId() . '/view');

        // Then
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Sprawdzenie nagłówków - inline zamiast attachment
        $this->assertTrue($response->headers->has('Content-Disposition'));
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('inline', $contentDisposition);
        $this->assertStringContainsString('TestImage.jpg', $contentDisposition);

        // Sprawdzenie Content-Type dla obrazu
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));

        // Cleanup
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }
    }
}
