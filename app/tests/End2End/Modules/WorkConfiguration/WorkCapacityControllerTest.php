<?php

namespace App\Tests\End2End\Modules\WorkConfiguration;

use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use App\System\Test\ApiTestCase;
use DateTimeImmutable;

class WorkCapacityControllerTest extends ApiTestCase
{
    private WorkCapacityRepository $workCapacityRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->workCapacityRepository = $this->get(WorkCapacityRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateWorkCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        $dateFrom = '2026-03-15';
        $capacity = 8.5;

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => $dateFrom,
            'capacity' => $capacity,
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($dateFrom, $responseData['dateFrom']);
        $this->assertEquals($capacity, $responseData['capacity']);

        // Verify in database
        $entity = $this->workCapacityRepository->find($responseData['id']);
        $this->assertNotNull($entity);
        $this->assertEquals($dateFrom, $entity->getDateFrom()->format('Y-m-d'));
        $this->assertEquals($capacity, $entity->getCapacity());
    }

    public function testShouldUpdateExistingWorkCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $dateFrom = DateTimeImmutable::createFromFormat('Y-m-d', '2026-03-20');
        $existingCapacity = $this->workCapacityRepository->upsert($dateFrom, 8.0);
        $this->getManager()->clear();

        $newCapacity = 10.5;

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => '2026-03-20',
            'capacity' => $newCapacity,
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($existingCapacity->getId(), $responseData['id']);
        $this->assertEquals($newCapacity, $responseData['capacity']);

        // Verify update in database
        $entity = $this->workCapacityRepository->find($existingCapacity->getId());
        $this->assertEquals($newCapacity, $entity->getCapacity());
    }

    public function testShouldFailCreateWithoutRequiredFields(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When - missing capacity
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => '2026-03-15',
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldFailCreateWithInvalidDateFormat(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => '15-03-2026', // Invalid format
            'capacity' => 8.0,
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Y-m-d', $responseData['error']);
    }

    public function testShouldFailCreateWithInvalidCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When - negative capacity
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => '2026-03-15',
            'capacity' => -5.0,
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldFailCreateWithNonNumericCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('POST', '/work-configuration/capacity', [
            'dateFrom' => '2026-03-15',
            'capacity' => 'invalid',
        ]);

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldListAllWorkCapacitiesWhenNoRangeProvided(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $date1 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-15 00:00:00');
        $date2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-18 00:00:00');
        $date3 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-25 00:00:00');

        $manager = $this->getManager();
        $capacity1 = new WorkCapacity($date1, 8.0);
        $capacity2 = new WorkCapacity($date2, 10.5);
        $capacity3 = new WorkCapacity($date3, 12.0);

        $manager->persist($capacity1);
        $manager->persist($capacity2);
        $manager->persist($capacity3);
        $manager->flush();
        $manager->clear();

        // When - no range parameters
        $client->xmlHttpRequest('GET', '/work-configuration/capacity');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(3, $responseData, 'Expected all 3 capacities, got: ' . json_encode($responseData));

        $this->assertEquals('2030-11-25', $responseData[0]['dateFrom']);
        $this->assertEquals(12.0, $responseData[0]['capacity']);

        $this->assertEquals('2030-11-18', $responseData[1]['dateFrom']);
        $this->assertEquals(10.5, $responseData[1]['capacity']);

        $this->assertEquals('2030-11-15', $responseData[2]['dateFrom']);
        $this->assertEquals(8.0, $responseData[2]['capacity']);
    }

    public function testShouldListWorkCapacitiesByRangeFromAndTo(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $date1 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-15 00:00:00');
        $date2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-18 00:00:00');
        $date3 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-25 00:00:00');

        $manager = $this->getManager();
        $capacity1 = new WorkCapacity($date1, 8.0);
        $capacity2 = new WorkCapacity($date2, 10.5);
        $capacity3 = new WorkCapacity($date3, 12.0);

        $manager->persist($capacity1);
        $manager->persist($capacity2);
        $manager->persist($capacity3);
        $manager->flush();
        $manager->clear();

        // When - range from 2030-11-15 to 2030-11-20
        $client->xmlHttpRequest('GET', '/work-configuration/capacity?startDate=2030-11-15&endDate=2030-11-20');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData, 'Expected 2 capacities in range, got: ' . json_encode($responseData));

        $this->assertEquals('2030-11-18', $responseData[0]['dateFrom']);
        $this->assertEquals(10.5, $responseData[0]['capacity']);

        $this->assertEquals('2030-11-15', $responseData[1]['dateFrom']);
        $this->assertEquals(8.0, $responseData[1]['capacity']);
    }

    public function testShouldListWorkCapacitiesByRangeFrom(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $date1 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-15 00:00:00');
        $date2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-18 00:00:00');
        $date3 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-25 00:00:00');

        $manager = $this->getManager();
        $capacity1 = new WorkCapacity($date1, 8.0);
        $capacity2 = new WorkCapacity($date2, 10.5);
        $capacity3 = new WorkCapacity($date3, 12.0);

        $manager->persist($capacity1);
        $manager->persist($capacity2);
        $manager->persist($capacity3);
        $manager->flush();
        $manager->clear();

        // When - only startDate provided
        $client->xmlHttpRequest('GET', '/work-configuration/capacity?startDate=2030-11-18');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(
            2,
            $responseData,
            'Expected 2 capacities from startDate, got: ' . json_encode($responseData)
        );

        $this->assertEquals('2030-11-25', $responseData[0]['dateFrom']);
        $this->assertEquals(12.0, $responseData[0]['capacity']);

        $this->assertEquals('2030-11-18', $responseData[1]['dateFrom']);
        $this->assertEquals(10.5, $responseData[1]['capacity']);
    }

    public function testShouldListWorkCapacitiesByRangeTo(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $date1 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-15 00:00:00');
        $date2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-18 00:00:00');
        $date3 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2030-11-25 00:00:00');

        $manager = $this->getManager();
        $capacity1 = new WorkCapacity($date1, 8.0);
        $capacity2 = new WorkCapacity($date2, 10.5);
        $capacity3 = new WorkCapacity($date3, 12.0);

        $manager->persist($capacity1);
        $manager->persist($capacity2);
        $manager->persist($capacity3);
        $manager->flush();
        $manager->clear();

        // When - only endDate provided
        $client->xmlHttpRequest('GET', '/work-configuration/capacity?endDate=2030-11-18');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData, 'Expected 2 capacities up to endDate, got: ' . json_encode($responseData));

        $this->assertEquals('2030-11-18', $responseData[0]['dateFrom']);
        $this->assertEquals(10.5, $responseData[0]['capacity']);

        $this->assertEquals('2030-11-15', $responseData[1]['dateFrom']);
        $this->assertEquals(8.0, $responseData[1]['capacity']);
    }

    public function testShouldFailListWithInvalidDateFormat(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('GET', '/work-configuration/capacity?startDate=01-04-2026&endDate=2026-04-30');

        // Then
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testShouldDeleteWorkCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        $dateFrom = DateTimeImmutable::createFromFormat('Y-m-d', '2026-05-01');
        $capacity = $this->workCapacityRepository->upsert($dateFrom, 8.0);
        $capacityId = $capacity->getId();
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('DELETE', '/work-configuration/capacity/' . $capacityId);

        // Then
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        // Verify deletion in database
        $this->getManager()->clear();
        $entity = $this->workCapacityRepository->find($capacityId);
        $this->assertNull($entity);
    }

    public function testShouldFailDeleteNonExistentCapacity(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('DELETE', '/work-configuration/capacity/99999');

        // Then
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyListWhenNoCapacitiesInRange(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->xmlHttpRequest('GET', '/work-configuration/capacity?startDate=2040-01-01&endDate=2040-12-31');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(0, $responseData);
    }
}
