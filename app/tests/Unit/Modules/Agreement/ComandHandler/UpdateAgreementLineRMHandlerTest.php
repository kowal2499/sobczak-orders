<?php

namespace App\Tests\Unit\Modules\Agreement\ComandHandler;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Agreement\CommandHandler\UpdateAgreementLineRMHandler;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\Test\InMemoryAgreementLineRepository;
use App\Module\Agreement\Repository\Test\InMemoryAgreementLineRMRepository;
use App\Module\Production\Factor\FactorCalculator;
use App\Service\UploaderHelper;
use App\Tests\Utilities\PrivateProperty;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;

class UpdateAgreementLineRMHandlerTest extends TestCase
{
    private InMemoryAgreementLineRMRepository $agreementLineRMRepository;
    private InMemoryAgreementLineRepository $agreementLineRepository;
    private UpdateAgreementLineRMHandler $handler;

    protected function setUp(): void
    {
        $this->agreementLineRMRepository = new InMemoryAgreementLineRMRepository();
        $this->agreementLineRepository = new InMemoryAgreementLineRepository();

        $this->handler = new UpdateAgreementLineRMHandler(
            $this->createMock(LoggerInterface::class),
            $this->agreementLineRepository,
            $this->agreementLineRMRepository,
            new FactorCalculator(),
            new UploaderHelper(
                'public/uploads',
                'public/thumbs',
                $this->createMock(RequestStackContext::class),
                'https://somehost',
                'uploads',
                'thumbs',
            )
        );
        parent::setUp();
    }

    public function testShouldAddAttachmentToReadModel(): void
    {
        // Given
        $customer = $this->getCustomer();
        $product = $this->getProduct();
        $agreement = $this->getAgreement();
        $agreementLine = $this->getAgreementLine();

        $attachment = $this->getAttachment([
            'name' => 'test.txt',
            'originalName' => 'test.txt',
            'extension' => 'txt'
        ]);

        // Konfiguracja relacji
        $agreement->setCustomer($customer);
        $agreement->setOrderNumber('ORDER-001');
        $agreement->setCreateDate(new \DateTime());
        $agreement->setStatus('DRAFT');
        $agreement->addAttachment($attachment);
        $agreement->addAgreementLine($agreementLine);

        $agreementLine->setAgreement($agreement);
        $agreementLine->setProduct($product);
        $agreementLine->setConfirmedDate(new \DateTime());
        $agreementLine->setArchived(false);
        $agreementLine->setDeleted(false);
        $agreementLine->setStatus(AgreementLine::STATUS_WAITING);

        $this->agreementLineRepository->save($agreementLine);

        $event = new UpdateAgreementLineRM($agreementLine->getId());

        // When
        $handler = $this->handler;
        $handler($event);

        // Then
        $readModel = $this->agreementLineRMRepository->find($agreementLine->getId());
        $this->assertInstanceOf(AgreementLineRM::class, $readModel);

        // Weryfikacja attachments
        $attachments = $readModel->getAttachments();
        $this->assertCount(1, $attachments);
        $attachmentData = $attachments[0]->toArray();
        $this->assertEquals('test.txt', $attachmentData['originalName']);
        $this->assertEquals('txt', $attachmentData['extension']);
        $this->assertEquals('https://somehost/uploads/agreements/test.txt', $attachmentData['path']);
        $this->assertEquals('https://somehost/thumbs/txt.svg', $attachmentData['thumbnail']);
    }

    private function getAgreementLine(): AgreementLine
    {
        $agreementLine = new AgreementLine();
        PrivateProperty::setId($agreementLine);
        return $agreementLine;
    }

    private function getAgreement(): Agreement
    {
        $agreement = new Agreement();
        PrivateProperty::setId($agreement);
        return $agreement;
    }

    private function getCustomer(): Customer
    {
        $customer = new Customer();
        PrivateProperty::setId($customer);
        $customer->setName('Test Customer');
        $customer->setFirstName('John');
        $customer->setLastName('Doe');
        $customer->setPhone('123456789');
        $customer->setEmail('test@example.com');
        $customer->setStreet('Test Street');
        $customer->setStreetNumber('1');
        $customer->setApartmentNumber('1');
        $customer->setPostalCode('00-000');
        $customer->setCity('Test City');
        $customer->setCountry('Test Country');
        return $customer;
    }

    private function getProduct(): Product
    {
        $product = new Product();
        PrivateProperty::setId($product);
        $product->setName('Test Product');
        $product->setFactor(1.0);
        return $product;
    }

    private function getAttachment(array $data): Attachment
    {
        $attachment = new Attachment();
        PrivateProperty::setId($attachment);
        $attachment->setName($data['name']);
        $attachment->setOriginalName($data['originalName']);
        $attachment->setExtension($data['extension']);

        return $attachment;
    }
}
