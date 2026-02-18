<?php

namespace App\Tests\End2End\Modules\WorkConfiguration;

use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use App\System\Test\ApiTestCase;
use DateTimeImmutable;

class WorkCapacityRepositoryTest extends ApiTestCase
{
    private WorkCapacityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->repository = $this->get(WorkCapacityRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testFindByRange_ShouldReturnCapacitiesInRange(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);
        $wc2 = $this->repository->upsert(new DateTimeImmutable('2026-02-01'), 9.0);
        $wc3 = $this->repository->upsert(new DateTimeImmutable('2026-03-01'), 10.0);

        // When
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-02-01'),
            new DateTimeImmutable('2026-03-01')
        );

        // Then
        $this->assertCount(2, $result);
        $this->assertEquals($wc2->getId(), $result[0]->getId()); // Sorted ASC
        $this->assertEquals($wc3->getId(), $result[1]->getId());
    }

    public function testFindByRange_ShouldReturnEarliestCapacityWhenStartDateNotExact(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);
        $wc2 = $this->repository->upsert(new DateTimeImmutable('2026-02-01'), 9.0);
        $wc3 = $this->repository->upsert(new DateTimeImmutable('2026-04-01'), 10.0);

        // When - zapytanie o zakres bez dokładnej daty startowej w bazie
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-02-15'), // Brak WorkCapacity dla tej daty
            new DateTimeImmutable('2026-04-01')
        );

        // Then - powinniśmy otrzymać najbliższe wcześniejsze (2026-02-01) + wszystkie do końca zakresu
        $this->assertCount(2, $result);
        $this->assertEquals($wc2->getId(), $result[0]->getId()); // 2026-02-01 (najbliższe wcześniejsze, sorted ASC)
        $this->assertEquals($wc3->getId(), $result[1]->getId()); // 2026-04-01
    }

    public function testFindByRange_ShouldReturnAtLeastOneCapacityWhenExists(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);

        // When - zapytanie o zakres po tym WorkCapacity
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-06-01'),
            new DateTimeImmutable('2026-12-31')
        );

        // Then - powinniśmy otrzymać przynajmniej to jedno WorkCapacity
        $this->assertCount(1, $result);
        $this->assertEquals($wc1->getId(), $result[0]->getId());
    }

    public function testFindByRange_ShouldReturnEmptyWhenNoCapacityBeforeStartDate(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-06-01'), 8.0);

        // When - zapytanie o zakres przed pierwszym WorkCapacity
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-03-31')
        );

        // Then - brak WorkCapacity przed datą startową
        $this->assertCount(0, $result);
    }

    public function testFindByRange_ShouldHandleNullDates(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);
        $wc2 = $this->repository->upsert(new DateTimeImmutable('2026-02-01'), 9.0);
        $wc3 = $this->repository->upsert(new DateTimeImmutable('2026-03-01'), 10.0);

        // When - brak daty początkowej i końcowej (wszystkie)
        $result = $this->repository->findByRange(null, null);

        // Then
        $this->assertCount(3, $result);
        $this->assertEquals($wc1->getId(), $result[0]->getId()); // Sorted ASC
    }

    public function testFindByRange_ShouldIncludeStartAndEndDateCapacities(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);
        $wc2 = $this->repository->upsert(new DateTimeImmutable('2026-02-01'), 9.0);
        $wc3 = $this->repository->upsert(new DateTimeImmutable('2026-03-01'), 10.0);

        // When - zakres obejmuje dokładne daty
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-03-01')
        );

        // Then - wszystkie trzy powinny być zwrócone
        $this->assertCount(3, $result);
    }

    public function testFindByRange_ShouldNotDuplicateResults(): void
    {
        // Given
        $wc1 = $this->repository->upsert(new DateTimeImmutable('2026-01-01'), 8.0);
        $wc2 = $this->repository->upsert(new DateTimeImmutable('2026-03-01'), 9.0);

        // When - zakres gdzie data startowa dokładnie odpowiada WorkCapacity
        $result = $this->repository->findByRange(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-03-01')
        );

        // Then - nie powinno być duplikatów
        $this->assertCount(2, $result);
        $ids = array_map(fn($wc) => $wc->getId(), $result);
        $this->assertCount(2, array_unique($ids));
    }
}
