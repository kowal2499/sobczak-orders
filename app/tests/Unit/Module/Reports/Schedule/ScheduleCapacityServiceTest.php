<?php

namespace App\Tests\Unit\Module\Reports\Schedule;

use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use PHPUnit\Framework\TestCase;

class ScheduleCapacityServiceTest extends TestCase
{
    private ScheduleCapacityService $service;
    private AgreementLineRMRepository $agreementLineRepo;
    private WorkCapacityRepository $workCapacityRepo;
    private WorkScheduleRepository $workScheduleRepo;

    protected function setUp(): void
    {
        $this->agreementLineRepo = $this->createMock(AgreementLineRMRepository::class);
        $this->workCapacityRepo = $this->createMock(WorkCapacityRepository::class);
        $this->workScheduleRepo = $this->createMock(WorkScheduleRepository::class);

        $this->service = new ScheduleCapacityService(
            $this->agreementLineRepo,
            $this->workCapacityRepo,
            $this->workScheduleRepo
        );
    }

    /**
     * Test kompleksowy sprawdzający scenariusz dwutygodniowy z:
     * - dniem z domyślną wartością capacity
     * - 2 zmianami capacity
     * - weekendami jako święta
     * - dodatkowym świętem w środku tygodnia
     * - AgreementLines poza zakresem zapytania ale w ramach tygodni
     */
    public function testCalculateBurnoutWithTwoWeeksComplexScenario(): void
    {
        // Given - zakres: wtorek 03.02 do piątku 13.02 (2 tygodnie)
        $start = new \DateTimeImmutable('2026-02-03');
        $end = new \DateTimeImmutable('2026-02-13');

        // Rozszerzony zakres: 02.02 (pn) - 15.02 (nd)
        $rangeStart = new \DateTimeImmutable('2026-02-02');
        $rangeEnd = new \DateTimeImmutable('2026-02-15');

        // Mock WorkCapacity
        $capacities = [
            $this->createWorkCapacity('2026-02-05', 2.5),
            $this->createWorkCapacity('2026-02-12', 3.0),
        ];

        // Mock WorkSchedule (święta)
        $holidays = [
            $this->createHoliday('2026-02-07'), // Sobota tydzień 1
            $this->createHoliday('2026-02-08'), // Niedziela tydzień 1
            $this->createHoliday('2026-02-11'), // Środa tydzień 2 - dodatkowe święto
            $this->createHoliday('2026-02-14'), // Sobota tydzień 2
            $this->createHoliday('2026-02-15'), // Niedziela tydzień 2
        ];

        // Mock AgreementLines
        $agreementLines = [
            // Tydzień 1
            $this->createAgreementLineRM('WEEK1-BEFORE', '2026-02-02', 0.3), // Przed zakresem
            $this->createAgreementLineRM('WEEK1-1', '2026-02-03', 0.5),
            $this->createAgreementLineRM('WEEK1-2', '2026-02-05', 0.7),
            // Tydzień 2
            $this->createAgreementLineRM('WEEK2-1', '2026-02-10', 1.2),
            $this->createAgreementLineRM('WEEK2-2', '2026-02-12', 0.8),
            $this->createAgreementLineRM('WEEK2-AFTER', '2026-02-14', 0.4), // Po zakresie
        ];

        // Setup mocks
        $this->setupMocks($capacities, $holidays, $agreementLines);

        // When
        $result = $this->service->calculateBurnout($start, $end);

        // Then
        $this->assertCount(11, $result, 'Powinno zwrócić 11 dni (03.02 - 13.02)');

        // Tydzień 1: capacity = 9.5714, burned = 1.5
        // dni 0-3: wtorek-piątek (03-06.02)
        for ($i = 0; $i <= 3; $i++) {
            $this->assertEquals(9.5714, $result[$i]->capacity, "Dzień {$i}: błędna capacity");
            $this->assertEquals(1.5, $result[$i]->capacityBurned, "Dzień {$i}: błędny burned");
            $this->assertCount(3, $result[$i]->agreementLines, "Dzień {$i}: błędna liczba AL");

            $orderNumbers = $this->extractOrderNumbers($result[$i]->agreementLines);
            $this->assertContains('WEEK1-BEFORE', $orderNumbers, "Dzień {$i}: brak WEEK1-BEFORE");
            $this->assertContains('WEEK1-1', $orderNumbers, "Dzień {$i}: brak WEEK1-1");
            $this->assertContains('WEEK1-2', $orderNumbers, "Dzień {$i}: brak WEEK1-2");
        }

        // Weekend tydzień 1
        // dni 4-5: sobota-niedziela (07-08.02)
        for ($i = 4; $i <= 5; $i++) {
            $this->assertEquals(9.5714, $result[$i]->capacity, "Dzień {$i} (weekend): błędna capacity");
            $this->assertEquals(1.5, $result[$i]->capacityBurned, "Dzień {$i} (weekend): błędny burned");
        }

        // Tydzień 2: capacity = 11.0, burned = 2.4
        // dni 6-10: poniedziałek-piątek (09-13.02)
        for ($i = 6; $i <= 10; $i++) {
            $this->assertEquals(11.0, $result[$i]->capacity, "Dzień {$i}: błędna capacity");
            $this->assertEquals(2.4, $result[$i]->capacityBurned, "Dzień {$i}: błędny burned");
            $this->assertCount(3, $result[$i]->agreementLines, "Dzień {$i}: błędna liczba AL");

            $orderNumbers = $this->extractOrderNumbers($result[$i]->agreementLines);
            $this->assertContains('WEEK2-1', $orderNumbers, "Dzień {$i}: brak WEEK2-1");
            $this->assertContains('WEEK2-2', $orderNumbers, "Dzień {$i}: brak WEEK2-2");
            $this->assertContains('WEEK2-AFTER', $orderNumbers, "Dzień {$i}: brak WEEK2-AFTER");
        }

        // Sprawdź konkretne daty
        $this->assertEquals('2026-02-03', $result[0]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-04', $result[1]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-05', $result[2]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-06', $result[3]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-07', $result[4]->date->format('Y-m-d')); // Sobota
        $this->assertEquals('2026-02-08', $result[5]->date->format('Y-m-d')); // Niedziela
        $this->assertEquals('2026-02-09', $result[6]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-10', $result[7]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-11', $result[8]->date->format('Y-m-d')); // Środa święto
        $this->assertEquals('2026-02-12', $result[9]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-13', $result[10]->date->format('Y-m-d'));
    }

    /**
     * Test sprawdzający obliczenia capacity dla tygodnia 1:
     * - 3 dni z DEFAULT_CAPACITY (1.5238): pn, wt, śr
     * - 2 dni z capacity 2.5: czw, pt
     * - 2 święta (so, nd) - nie wliczane
     * = 1.5238*3 + 2.5*2 = 4.5714 + 5 = 9.5714
     */
    public function testCapacityCalculationForWeek1(): void
    {
        // Given
        $start = new \DateTimeImmutable('2026-02-03');
        $end = new \DateTimeImmutable('2026-02-06');

        $capacities = [
            $this->createWorkCapacity('2026-02-05', 2.5),
        ];

        $holidays = [
            $this->createHoliday('2026-02-07'),
            $this->createHoliday('2026-02-08'),
        ];

        $agreementLines = [];

        $this->setupMocks($capacities, $holidays, $agreementLines);

        // When
        $result = $this->service->calculateBurnout($start, $end);

        // Then
        $this->assertCount(4, $result);
        foreach ($result as $day) {
            $this->assertEquals(9.5714, $day->capacity);
        }
    }

    /**
     * Test sprawdzający obliczenia capacity dla tygodnia 2:
     * - 2 dni z capacity 2.5: pn, wt
     * - 1 święto (śr) - nie wliczane
     * - 2 dni z capacity 3.0: czw, pt
     * - 2 święta (so, nd) - nie wliczane
     * = 2.5*2 + 3.0*2 = 5 + 6 = 11.0
     */
    public function testCapacityCalculationForWeek2(): void
    {
        // Given
        $start = new \DateTimeImmutable('2026-02-09');
        $end = new \DateTimeImmutable('2026-02-13');

        $capacities = [
            $this->createWorkCapacity('2026-02-05', 2.5),
            $this->createWorkCapacity('2026-02-12', 3.0),
        ];

        $holidays = [
            $this->createHoliday('2026-02-11'), // Środa
            $this->createHoliday('2026-02-14'),
            $this->createHoliday('2026-02-15'),
        ];

        $agreementLines = [];

        $this->setupMocks($capacities, $holidays, $agreementLines);

        // When
        $result = $this->service->calculateBurnout($start, $end);

        // Then
        $this->assertCount(5, $result);
        foreach ($result as $day) {
            $this->assertEquals(11.0, $day->capacity);
        }
    }

    /**
     * Test sprawdzający filtrowanie AgreementLines po tygodniach
     */
    public function testAgreementLinesFilteringByWeek(): void
    {
        // Given
        $start = new \DateTimeImmutable('2026-02-03');
        $end = new \DateTimeImmutable('2026-02-13');

        $capacities = [];
        $holidays = [];

        $agreementLines = [
            // Tydzień 1 (02-08.02)
            $this->createAgreementLineRM('WEEK1-BEFORE', '2026-02-02', 0.3),
            $this->createAgreementLineRM('WEEK1-1', '2026-02-03', 0.5),
            // Tydzień 2 (09-15.02)
            $this->createAgreementLineRM('WEEK2-1', '2026-02-10', 1.2),
            $this->createAgreementLineRM('WEEK2-AFTER', '2026-02-14', 0.4),
            // Poza zakresem
            $this->createAgreementLineRM('OUT-1', '2026-01-31', 1.0),
            $this->createAgreementLineRM('OUT-2', '2026-02-20', 1.0),
        ];

        $this->setupMocks($capacities, $holidays, $agreementLines);

        // When
        $result = $this->service->calculateBurnout($start, $end);

        // Then
        // Sprawdź tydzień 1 (dni 0-5)
        for ($i = 0; $i <= 5; $i++) {
            $orderNumbers = $this->extractOrderNumbers($result[$i]->agreementLines);
            $this->assertContains('WEEK1-BEFORE', $orderNumbers, "Dzień {$i}");
            $this->assertContains('WEEK1-1', $orderNumbers, "Dzień {$i}");
            $this->assertNotContains('WEEK2-1', $orderNumbers, "Dzień {$i}");
            $this->assertNotContains('OUT-1', $orderNumbers, "Dzień {$i}");
        }

        // Sprawdź tydzień 2 (dni 6-10)
        for ($i = 6; $i <= 10; $i++) {
            $orderNumbers = $this->extractOrderNumbers($result[$i]->agreementLines);
            $this->assertContains('WEEK2-1', $orderNumbers, "Dzień {$i}");
            $this->assertContains('WEEK2-AFTER', $orderNumbers, "Dzień {$i}");
            $this->assertNotContains('WEEK1-1', $orderNumbers, "Dzień {$i}");
            $this->assertNotContains('OUT-2', $orderNumbers, "Dzień {$i}");
        }
    }

    /**
     * Test sprawdzający sumowanie capacityBurned
     */
    public function testCapacityBurnedCalculation(): void
    {
        // Given
        $start = new \DateTimeImmutable('2026-02-03');
        $end = new \DateTimeImmutable('2026-02-03');

        $capacities = [];
        $holidays = [];

        $agreementLines = [
            $this->createAgreementLineRM('AL-1', '2026-02-03', 0.5),
            $this->createAgreementLineRM('AL-2', '2026-02-03', 0.7),
            $this->createAgreementLineRM('AL-3', '2026-02-03', 0.3),
        ];

        $this->setupMocks($capacities, $holidays, $agreementLines);

        // When
        $result = $this->service->calculateBurnout($start, $end);

        // Then
        $this->assertCount(1, $result);
        $this->assertEquals(1.5, $result[0]->capacityBurned); // 0.5 + 0.7 + 0.3
    }

    // Helper methods

    private function setupMocks(array $capacities, array $holidays, array $agreementLines): void
    {
        $this->workCapacityRepo
            ->method('findByRange')
            ->willReturn($capacities);

        $this->workScheduleRepo
            ->method('findHolidaysByRange')
            ->willReturn($holidays);

        $queryMock = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $queryMock
            ->method('getResult')
            ->willReturn($agreementLines);

        $this->agreementLineRepo
            ->method('search')
            ->willReturn($queryMock);
    }

    private function createWorkCapacity(string $date, float $capacity): WorkCapacity
    {
        return new WorkCapacity(new \DateTimeImmutable($date), $capacity);
    }

    private function createHoliday(string $date): WorkSchedule
    {
        $holiday = $this->createMock(WorkSchedule::class);
        $holiday->method('getDate')->willReturn(new \DateTimeImmutable($date));
        return $holiday;
    }

    private function createAgreementLineRM(string $orderNumber, string $confirmedDate, float $factor): AgreementLineRM
    {
        $agreementLine = $this->createMock(AgreementLineRM::class);
        $agreementLine->method('getOrderNumber')->willReturn($orderNumber);
        $agreementLine->method('getConfirmedDate')->willReturn(new \DateTime($confirmedDate));
        $agreementLine->method('getFactor')->willReturn($factor);
        return $agreementLine;
    }

    /**
     * @param AgreementLineRM[] $agreementLines
     * @return string[]
     */
    private function extractOrderNumbers(array $agreementLines): array
    {
        return array_map(fn($al) => $al->getOrderNumber(), $agreementLines);
    }
}
