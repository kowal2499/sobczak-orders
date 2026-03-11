<?php

namespace App\Tests\End2End\Modules\WorkConfiguration;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use App\System\Test\ApiTestCase;
use DateTimeImmutable;

class WorkScheduleControllerTest extends ApiTestCase
{
    private WorkScheduleRepository $workScheduleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->workScheduleRepository = $this->get(WorkScheduleRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateWorkScheduleDay(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        $date = '2026-03-15';
        $dayType = 'holiday';
        $description = 'Test holiday';

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/schedule', [
            'date' => $date,
            'dayType' => $dayType,
            'description' => $description,
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($date, $responseData['date']);
        $this->assertEquals($dayType, $responseData['dayType']);
        $this->assertEquals($description, $responseData['description']);

        // Verify in database
        $entity = $this->workScheduleRepository->find($responseData['id']);
        $this->assertNotNull($entity);
        $this->assertEquals($date, $entity->getDate()->format('Y-m-d'));
        $this->assertEquals(ScheduleDayType::Holiday, $entity->getDayType());
        $this->assertEquals($description, $entity->getDescription());
    }

    public function testShouldUpdateExistingWorkScheduleDay(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);

        $date = DateTimeImmutable::createFromFormat('Y-m-d', '2026-03-20');
        $existingSchedule = $this->workScheduleRepository->upsert(
            $date,
            ScheduleDayType::Holiday,
            'Original description'
        );
        $this->getManager()->clear();

        $newDayType = 'working';
        $newDescription = 'Updated to working day';

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/schedule', [
            'date' => '2026-03-20',
            'dayType' => $newDayType,
            'description' => $newDescription,
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($existingSchedule->getId(), $responseData['id']);
        $this->assertEquals($newDayType, $responseData['dayType']);
        $this->assertEquals($newDescription, $responseData['description']);

        // Verify update in database
        $entity = $this->workScheduleRepository->find($existingSchedule->getId());
        $this->assertEquals(ScheduleDayType::Working, $entity->getDayType());
        $this->assertEquals($newDescription, $entity->getDescription());
    }

    public function testShouldFailCreateWithoutRequiredFields(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When - missing dayType
        $client->xmlHttpRequest('POST', '/work-configuration/schedule', [
            'date' => '2026-03-15',
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldFailCreateWithInvalidDateFormat(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/schedule', [
            'date' => '15-03-2026', // Invalid format
            'dayType' => 'holiday',
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Y-m-d', $responseData['error']);
    }

    public function testShouldFailCreateWithInvalidDayType(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/schedule', [
            'date' => '2026-03-15',
            'dayType' => 'invalid_type',
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('working, holiday, other', $responseData['error']);
    }

    public function testShouldDeleteWorkSchedule(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);

        $date = DateTimeImmutable::createFromFormat('Y-m-d', '2026-05-01');
        $schedule = $this->workScheduleRepository->upsert(
            $date,
            ScheduleDayType::Holiday,
            'To be deleted'
        );
        $scheduleId = $schedule->getId();
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('DELETE', '/work-configuration/schedule/' . $scheduleId);

        // Then
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        // Verify deletion in database
        $this->getManager()->clear();
        $entity = $this->workScheduleRepository->find($scheduleId);
        $this->assertNull($entity);
    }

    public function testShouldFailDeleteNonExistentSchedule(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('DELETE', '/work-configuration/schedule/99999');

        // Then
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
