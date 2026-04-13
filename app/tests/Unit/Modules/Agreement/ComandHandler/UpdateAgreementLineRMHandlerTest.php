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
use App\Module\Task\Entity\Task;
use App\Module\Task\Entity\TaskStatusLog;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use App\Service\UploaderHelper;
use App\Tests\Utilities\PrivateProperty;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;

class UpdateAgreementLineRMHandlerTest extends TestCase
{
    private InMemoryAgreementLineRMRepository $agreementLineRMRepository;
    private InMemoryAgreementLineRepository $agreementLineRepository;
    private UpdateAgreementLineRMHandler $handler;
    private string $tempThumbsPath;

    protected function setUp(): void
    {
        $this->agreementLineRMRepository = new InMemoryAgreementLineRMRepository();
        $this->agreementLineRepository = new InMemoryAgreementLineRepository();

        // Utwórz tymczasowy folder dla thumbs w testach
        $this->tempThumbsPath = sys_get_temp_dir() . '/test_thumbs_' . uniqid();
        if (!is_dir($this->tempThumbsPath)) {
            mkdir($this->tempThumbsPath, 0755, true);
        }

        // Skonfiguruj mock EntityManager aby zwracał mock TaskRepository
        $taskRepositoryMock = $this->createMock(\App\Module\Task\Repository\TaskRepository::class);
        $taskRepositoryMock->method('findByAgreementLine')->willReturn([]);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->method('getRepository')
            ->with(\App\Module\Task\Entity\Task::class)
            ->willReturn($taskRepositoryMock);

        $this->handler = new UpdateAgreementLineRMHandler(
            $this->createMock(LoggerInterface::class),
            $this->agreementLineRepository,
            $this->agreementLineRMRepository,
            new FactorCalculator(),
            new UploaderHelper(
                'public/uploads',
                $this->tempThumbsPath,
                $this->createMock(RequestStackContext::class),
                'https://somehost',
                'uploads',
                'thumbs',
            ),
            $entityManagerMock
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Wyczyść tymczasowy folder po testach
        if (is_dir($this->tempThumbsPath)) {
            $files = glob($this->tempThumbsPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempThumbsPath);
        }
        parent::tearDown();
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
        $this->assertEquals('/attachments/' . $attachment->getId() . '/download', $attachmentData['path']);
        $this->assertEquals('/attachments/' . $attachment->getId() . '/view', $attachmentData['viewPath']);
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

    public function testShouldIncludeTaskStatusLogsInReadModel(): void
    {
        // Given
        $customer = $this->getCustomer();
        $product = $this->getProduct();
        $agreement = $this->getAgreement();
        $agreementLine = $this->getAgreementLine();

        $agreement->setCustomer($customer);
        $agreement->setOrderNumber('ORDER-002');
        $agreement->setCreateDate(new \DateTime());
        $agreement->setStatus('DRAFT');
        $agreement->addAgreementLine($agreementLine);

        $agreementLine->setAgreement($agreement);
        $agreementLine->setProduct($product);
        $agreementLine->setConfirmedDate(new \DateTime());
        $agreementLine->setArchived(false);
        $agreementLine->setDeleted(false);
        $agreementLine->setStatus(AgreementLine::STATUS_WAITING);

        // Budujemy task z dwoma wpisami historii statusów
        $task = $this->buildTask($agreementLine);
        $logInitial = new TaskStatusLog($task, TaskStatusEnum::AWAITS->value, null, null);
        $logChange  = new TaskStatusLog($task, TaskStatusEnum::PENDING->value, TaskStatusEnum::AWAITS->value, null);
        $task->addStatusLog($logInitial);
        $task->addStatusLog($logChange);

        // Podmień mock repozytorium tasków aby zwrócił przygotowany task
        $taskRepositoryMock = $this->createMock(\App\Module\Task\Repository\TaskRepository::class);
        $taskRepositoryMock->method('findByAgreementLine')->willReturn([$task]);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->method('getRepository')
            ->with(\App\Module\Task\Entity\Task::class)
            ->willReturn($taskRepositoryMock);

        $handler = $this->buildHandler($entityManagerMock);

        $this->agreementLineRepository->save($agreementLine);

        // When
        $handler(new UpdateAgreementLineRM($agreementLine->getId()));

        // Then
        $readModel = $this->agreementLineRMRepository->find($agreementLine->getId());
        $this->assertInstanceOf(AgreementLineRM::class, $readModel);

        $tasks = $readModel->getTasks();
        $this->assertCount(1, $tasks);
        $this->assertArrayHasKey('statusLogs', $tasks[0]);

        $statusLogs = $tasks[0]['statusLogs'];
        $this->assertCount(2, $statusLogs);

        $this->assertNull($statusLogs[0]['previousStatus']);
        $this->assertEquals(TaskStatusEnum::AWAITS->value, $statusLogs[0]['currentStatus']);

        $this->assertEquals(TaskStatusEnum::AWAITS->value, $statusLogs[1]['previousStatus']);
        $this->assertEquals(TaskStatusEnum::PENDING->value, $statusLogs[1]['currentStatus']);
    }

    private function buildTask(AgreementLine $agreementLine): Task
    {
        $task = new Task();
        PrivateProperty::setId($task);
        $task->setAgreementLine($agreementLine);
        $task->setStatusEnum(TaskStatusEnum::AWAITS);
        $task->setTypeEnum(TaskTypeEnum::TASK_CUSTOM);
        $task->setTitle('Test task');
        $task->setIsDeleted(false);
        $task->setCreatedAt(new \DateTime());
        $task->setUpdatedAt(new \DateTime());
        return $task;
    }

    private function buildHandler(EntityManagerInterface $entityManagerMock): UpdateAgreementLineRMHandler
    {
        return new UpdateAgreementLineRMHandler(
            $this->createMock(LoggerInterface::class),
            $this->agreementLineRepository,
            $this->agreementLineRMRepository,
            new FactorCalculator(),
            new UploaderHelper(
                'public/uploads',
                $this->tempThumbsPath,
                $this->createMock(RequestStackContext::class),
                'https://somehost',
                'uploads',
                'thumbs',
            ),
            $entityManagerMock
        );
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
