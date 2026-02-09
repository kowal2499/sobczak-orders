<?php

namespace App\Tests\End2End\Modules\WorkConfiguration;

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

    public function testShouldListWorkSchedulesByRange(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);

        $date1 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-02-18 00:00:00');
        $date2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-02-21 00:00:00');
        $date3 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-02-25 00:00:00');

        $manager = $this->getManager();
        $schedule1 = new \App\Module\WorkConfiguration\Entity\WorkSchedule($date1, ScheduleDayType::Holiday, 'Day 1');
        $schedule2 = new \App\Module\WorkConfiguration\Entity\WorkSchedule($date2, ScheduleDayType::Working, 'Day 2');
        $schedule3 = new \App\Module\WorkConfiguration\Entity\WorkSchedule($date3, ScheduleDayType::Holiday, 'Day 3');

        $manager->persist($schedule1);
        $manager->persist($schedule2);
        $manager->persist($schedule3);
        $manager->flush();
        $manager->clear();

        // When
        $client->xmlHttpRequest('GET', '/work-configuration/schedule?startDate=2026-02-18&endDate=2026-02-23&type=holiday');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData, 'Expected 2 holiday schedule in range, got: ' . json_encode($responseData));

        $this->assertEquals('2026-02-18', $responseData[0]['date']);
        $this->assertEquals('holiday', $responseData[0]['dayType']);
        $this->assertEquals('Day 1', $responseData[0]['description']);
        $this->assertEquals('2026-02-22', $responseData[1]['date']);
        $this->assertEquals('holiday', $responseData[1]['dayType']);
        $this->assertEquals('weekend', $responseData[1]['description']);
    }

    public function testShouldFailListWithoutRequiredParameters(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When - missing endDate
        $client->xmlHttpRequest('GET', '/work-configuration/schedule?startDate=2026-04-01');

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldFailListWithInvalidDateFormat(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.schedule']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('GET', '/work-configuration/schedule?startDate=01-04-2026&endDate=2026-04-30');

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
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
