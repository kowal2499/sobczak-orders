<?php

namespace App\Tests\End2End\Modules\Task;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Task\Entity\Task;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class TaskControllerTest extends ApiTestCase
{
    private AgreementLineRMRepository $agreementLineRMRepository;
    private EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->agreementLineRMRepository = $this->get(AgreementLineRMRepository::class);
        $this->factory = new EntityFactory($this->getManager());
    }

    private function getTaskRepository()
    {
        return $this->getManager()->getRepository(Task::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateTask(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'agreementLineId' => $agreementLine->getId(),
            'dateStart' => '2026-04-01 08:00:00',
            'dateEnd' => '2026-04-05 17:00:00',
            'status' => TaskStatusEnum::AWAITS->value,
            'type' => TaskTypeEnum::TASK_CUSTOM->value,
            'title' => 'Test task',
            'description' => 'Test task description',
            'ownerId' => $user->getId(),
        ]));

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify in database
        $this->getManager()->clear();
        $tasks = $this->getTaskRepository()->findByAgreementLine($agreementLine);
        $this->assertCount(1, $tasks);

        $task = $tasks[0];
        $this->assertEquals($agreementLine->getId(), $task->getAgreementLine()->getId());
        $this->assertEquals('2026-04-01', $task->getDateStart()->format('Y-m-d'));
        $this->assertEquals('2026-04-05', $task->getDateEnd()->format('Y-m-d'));
        $this->assertEquals(TaskStatusEnum::AWAITS->value, $task->getStatus());
        $this->assertEquals(TaskTypeEnum::TASK_CUSTOM->value, $task->getType());
        $this->assertEquals('Test task', $task->getTitle());
        $this->assertEquals('Test task description', $task->getDescription());
        $this->assertEquals($user->getId(), $task->getOwner()->getId());
        $this->assertFalse($task->isDeleted());
    }

    public function testShouldUpdateTaskByOwner(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $task = $this->createTask($agreementLine, $user);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('PUT', '/tasks/' . $task->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'dateStart' => '2026-04-10 08:00:00',
            'dateEnd' => '2026-04-15 17:00:00',
            'status' => TaskStatusEnum::PENDING->value,
            'title' => 'Updated task',
            'description' => 'Updated task description',
        ]));

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify in database
        $this->getManager()->clear();
        $updatedTask = $this->getTaskRepository()->find($task->getId());
        $this->assertNotNull($updatedTask);
        $this->assertEquals('2026-04-10', $updatedTask->getDateStart()->format('Y-m-d'));
        $this->assertEquals('2026-04-15', $updatedTask->getDateEnd()->format('Y-m-d'));
        $this->assertEquals(TaskStatusEnum::PENDING->value, $updatedTask->getStatus());
        $this->assertEquals('Updated task', $updatedTask->getTitle());
        $this->assertEquals('Updated task description', $updatedTask->getDescription());
    }

    public function testShouldNotUpdateTaskByNonOwner(): void
    {
        // Given
        $owner = $this->createUser();
        $nonOwner = $this->createUser(['email' => 'nonowner@example.com']);
        $client = $this->login($nonOwner);

        $agreementLine = $this->createAgreementLine();
        $task = $this->createTask($agreementLine, $owner);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('PUT', '/tasks/' . $task->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'dateStart' => '2026-04-10 08:00:00',
            'dateEnd' => '2026-04-15 17:00:00',
            'status' => TaskStatusEnum::PENDING->value,
            'title' => 'Trying to update',
        ]));

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testShouldDeleteTaskByOwner(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $task = $this->createTask($agreementLine, $user);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('DELETE', '/tasks/' . $task->getId());

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify soft delete
        $this->getManager()->clear();
        $deletedTask = $this->getTaskRepository()->find($task->getId());
        $this->assertNull($deletedTask); // Should not be found by default (isDeleted filter)

        // Verify it exists but is marked as deleted using parent findBy to bypass filter
        $taskRepository = $this->getManager()->getRepository(Task::class);
        $qb = $taskRepository->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.isDeleted = true')
            ->setParameter('id', $task->getId());
        $deletedTasks = $qb->getQuery()->getResult();

        $this->assertCount(1, $deletedTasks);
        $this->assertTrue($deletedTasks[0]->isDeleted());
    }

    public function testShouldNotDeleteTaskByNonOwner(): void
    {
        // Given
        $owner = $this->createUser();
        $nonOwner = $this->createUser(['email' => 'nonowner@example.com']);
        $client = $this->login($nonOwner);

        $agreementLine = $this->createAgreementLine();
        $task = $this->createTask($agreementLine, $owner);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('DELETE', '/tasks/' . $task->getId());

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testShouldUpdateAgreementLineRMAfterTaskCreation(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'agreementLineId' => $agreementLine->getId(),
            'dateStart' => '2026-04-01 08:00:00',
            'dateEnd' => '2026-04-05 17:00:00',
            'status' => TaskStatusEnum::AWAITS->value,
            'type' => TaskTypeEnum::TASK_CUSTOM->value,
            'title' => 'Test task for RM',
            'description' => 'Test description',
        ]));

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Verify read model was updated
        $this->getManager()->clear();
        $readModel = $this->agreementLineRMRepository->find($agreementLine->getId());
        $this->assertNotNull($readModel);

        $tasks = $readModel->getTasks();
        $this->assertCount(1, $tasks);
        $this->assertEquals('Test task for RM', $tasks[0]['title']);
        $this->assertEquals('Test description', $tasks[0]['description']);
    }

    public function testShouldValidateDateEndGreaterThanDateStart(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When - dateEnd before dateStart (obie daty podane)
        $client->request('POST', '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'agreementLineId' => $agreementLine->getId(),
            'dateStart' => '2026-04-10 08:00:00',
            'dateEnd' => '2026-04-05 17:00:00',
            'status' => TaskStatusEnum::AWAITS->value,
            'type' => TaskTypeEnum::TASK_CUSTOM->value,
        ]));

        // Then
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testShouldCreateTaskWithoutDates(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When - task bez dat
        $client->request('POST', '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'agreementLineId' => $agreementLine->getId(),
            'status' => TaskStatusEnum::PENDING->value,
            'type' => TaskTypeEnum::TASK_CONFIRM_REALIZATION_DATE->value,
            'title' => 'Task without dates',
            'description' => 'This task has no dates set',
        ]));

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify in database
        $this->getManager()->clear();
        $tasks = $this->getTaskRepository()->findByAgreementLine($agreementLine);
        $this->assertCount(1, $tasks);

        $task = $tasks[0];
        $this->assertNull($task->getDateStart());
        $this->assertNull($task->getDateEnd());
        $this->assertEquals(TaskStatusEnum::PENDING->value, $task->getStatus());
        $this->assertEquals('Task without dates', $task->getTitle());
    }

    public function testShouldUpdateTaskStatus(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        $agreementLine = $this->createAgreementLine();
        $task = $this->createTask($agreementLine, $user);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/tasks/' . $task->getId() . '/status', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'status' => TaskStatusEnum::COMPLETED->value,
        ]));

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify status updated in database
        $this->getManager()->clear();
        $updatedTask = $this->getTaskRepository()->find($task->getId());
        $this->assertNotNull($updatedTask);
        $this->assertEquals(TaskStatusEnum::COMPLETED->value, $updatedTask->getStatus());
    }

    private function createAgreementLine(): AgreementLine
    {
        $customer = $this->factory->make(Customer::class);
        $product = $this->factory->make(Product::class);

        $agreement = $this->factory->make(\App\Entity\Agreement::class, [
            'customer' => $customer,
        ]);

        $agreementLine = $this->factory->make(AgreementLine::class, [
            'agreement' => $agreement,
            'product' => $product,
        ]);

        return $agreementLine;
    }

    private function createTask(AgreementLine $agreementLine, ?User $owner = null): Task
    {
        $task = new Task();
        $task->setAgreementLine($agreementLine);
        $task->setDateStart(new \DateTime('2026-04-01'));
        $task->setDateEnd(new \DateTime('2026-04-05'));
        $task->setStatusEnum(TaskStatusEnum::AWAITS);
        $task->setTypeEnum(TaskTypeEnum::TASK_CUSTOM);
        $task->setTitle('Original task');
        $task->setDescription('Original description');
        $task->setOwner($owner);
        $task->setIsDeleted(false);

        $this->getManager()->persist($task);

        return $task;
    }
}
