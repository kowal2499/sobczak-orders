<?php

namespace App\Tests\Unit\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\CustomerRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\Metric\DepartmentsBonusOnTimeMetricStrategy;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\Service\DefaultHolidaysProvider;
use App\Module\WorkConfiguration\Service\WorkingDayCalculator;
use App\Module\WorkConfiguration\Service\WorkScheduleService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class DepartmentsBonusOnTimeMetricStrategyTest extends TestCase
{
    public function testMarksOnTimeWhenCompletedInsideWindow(): void
    {
        // ukończenie w oknie [01-05, 10-05]
        $line = $this->makeLine(1, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getOnTime());
        $this->assertSame(2.0, $result[0]->getFactors()->factor);
    }

    public function testIncludesButMarksOffTimeWhenCompletedAfterWindow(): void
    {
        // ukończenie po oknie — rekord obecny, ale onTime=false
        $line = $this->makeLine(2, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-20 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testMarksOffTimeWhenCompletedBeforeWindow(): void
    {
        $line = $this->makeLine(3, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-10'),
                dateEnd: new \DateTime('2026-05-20'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testMarksOffTimeWhenWindowMissing(): void
    {
        $line = $this->makeLine(4, [
            $this->prod(
                'dpt03',
                dateStart: null,
                dateEnd: null,
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testTreatsWindowBoundariesAsOnTime(): void
    {
        // ukończenie ostatniego dnia okna wieczorem — nadal w terminie (koniec = 23:59:59)
        $line = $this->makeLine(5, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-10 23:30:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getOnTime());
    }

    public function testEmitsOutOfRangeRecordForOtherDepartmentsOfReportedLine(): void
    {
        // dpt03 rozliczony w maju, dpt05 dopiero w czerwcu — dpt05 trafia do wyniku jako "poza zakresem"
        $line = $this->makeLine(6, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
            $this->prod(
                'dpt05',
                dateStart: new \DateTime('2026-06-04'),
                dateEnd: new \DateTime('2026-06-11'),
                completedAt: new \DateTime('2026-06-08 12:00:00'),
                bonus: new AssembledFactorDTO(3.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertCount(2, $result);

        $this->assertSame('dpt03', $result[0]->getDepartmentSlug());
        $this->assertTrue($result[0]->getInRange());
        $this->assertTrue($result[0]->getOnTime());

        $outOfRange = $result[1];
        $this->assertSame('dpt05', $outOfRange->getDepartmentSlug());
        $this->assertFalse($outOfRange->getInRange());
        $this->assertFalse($outOfRange->getOnTime());
        $this->assertNull($outOfRange->getFactors());
        // okno produkcji musi zostać — front pokazuje je w popoverze
        $this->assertSame('2026-06-04', $outOfRange->getDateStart()->format('Y-m-d'));
        $this->assertSame('2026-06-11', $outOfRange->getDateEnd()->format('Y-m-d'));
    }

    public function testSkipsLineWithoutAnyQualifyingProduction(): void
    {
        // żaden dział nie rozlicza się w maju — linia nie trafia do raportu wcale (brak "sierot")
        $line = $this->makeLine(7, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-06-01'),
                dateEnd: new \DateTime('2026-06-10'),
                completedAt: new \DateTime('2026-06-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->computeMay([$line]);

        $this->assertSame([], $result);
    }

    public function testUsesFullCascadeFactorWhenReadModelHasIt(): void
    {
        // pełna kaskada (z korektą z tego raportu) wygrywa z factorBonus
        $production = $this->prod(
            'dpt03',
            dateStart: new \DateTime('2026-05-01'),
            dateEnd: new \DateTime('2026-05-10'),
            completedAt: new \DateTime('2026-05-05 12:00:00'),
            bonus: new AssembledFactorDTO(2.0),
        );
        $production->setFactorBonusCompletedTasks(new AssembledFactorDTO(2.5));

        $result = $this->computeMay([$this->makeLine(8, [$production])]);

        $this->assertSame(2.5, $result[0]->getFactors()->factor);
    }

    public function testFallsBackToBonusFactorForStaleReadModel(): void
    {
        // starszy wiersz RM nie ma jeszcze pola pełnej kaskady — używamy factorBonus
        $result = $this->computeMay([$this->makeLine(9, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ])]);

        $this->assertSame(2.0, $result[0]->getFactors()->factor);
    }

    public function testToleranceExtendsWindowByWorkingDays(): void
    {
        // okno kończy się w piątek 2026-05-15, ukończenie we wtorek 2026-05-19 — 2 dni robocze po terminie
        $line = $this->makeLine(10, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-19 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $this->assertFalse($this->computeMay([$line])[0]->getOnTime());
        $this->assertTrue($this->computeMay([$line], 5)[0]->getOnTime());
    }

    public function testMarksRecordsAcceptedOnlyThanksToTolerance(): void
    {
        // ukończenie 2 dni robocze po oknie — premia wyłącznie dzięki widełkom
        $delayed = $this->makeLine(14, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-19 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);
        // ukończenie w samym oknie — widełki nic nie zmieniają
        $inWindow = $this->makeLine(15, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-13 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $this->assertTrue($this->computeMay([$delayed], 5)[0]->getWithinTolerance());
        $this->assertFalse($this->computeMay([$inWindow], 5)[0]->getWithinTolerance());
        // bez widełek nie ma premii, więc nie ma też czego oznaczać
        $this->assertFalse($this->computeMay([$delayed])[0]->getWithinTolerance());
    }

    public function testReportsTimelinessDeviationInWorkingDays(): void
    {
        // okno kończy się w piątek 2026-05-15, ukończenie w środę 2026-05-20
        // => 6 dni kalendarzowych, ale 3 robocze
        $delayed = $this->makeLine(16, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-20 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);
        // ukończenie przed oknem => wartość ujemna
        $early = $this->makeLine(17, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-20'),
                dateEnd: new \DateTime('2026-05-25'),
                completedAt: new \DateTime('2026-05-18 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);
        // ukończenie w oknie => zero
        $inWindow = $this->makeLine(18, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-13 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $this->assertSame(3, $this->computeMay([$delayed])[0]->getTimelinessWorkingDays());
        $this->assertSame(-2, $this->computeMay([$early])[0]->getTimelinessWorkingDays());
        $this->assertSame(0, $this->computeMay([$inWindow])[0]->getTimelinessWorkingDays());
    }

    public function testToleranceDoesNotCountWeekends(): void
    {
        // okno kończy się w piątek 2026-05-15; 1 dzień roboczy widełek sięga poniedziałku 2026-05-18,
        // ale nie wtorku — weekend nie konsumuje widełek
        $line = $this->makeLine(11, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-18 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);
        $lineNextDay = $this->makeLine(12, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-11'),
                dateEnd: new \DateTime('2026-05-15'),
                completedAt: new \DateTime('2026-05-19 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $this->assertTrue($this->computeMay([$line], 1)[0]->getOnTime());
        $this->assertFalse($this->computeMay([$lineNextDay], 1)[0]->getOnTime());
    }

    public function testToleranceExtendsWindowBackwards(): void
    {
        // ukończenie 2 dni robocze przed planowanym startem (poniedziałek 2026-05-18)
        $line = $this->makeLine(13, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-20'),
                dateEnd: new \DateTime('2026-05-25'),
                completedAt: new \DateTime('2026-05-18 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $this->assertFalse($this->computeMay([$line])[0]->getOnTime());
        $this->assertTrue($this->computeMay([$line], 2)[0]->getOnTime());
    }

    /**
     * Liczy miernik za maj 2026. Domyślnie z widełkami 0, czyli regułą ścisłą — testy widełek
     * podają wartość jawnie.
     *
     * @return \App\Module\Reports\Production\DTO\ProductionReportRecordDTO[]
     */
    private function computeMay(array $lines, int $toleranceDays = 0): array
    {
        return $this->makeStrategy($lines)->compute(
            new \DateTime('2026-05-01'),
            new \DateTime('2026-05-31'),
            false,
            ['toleranceDays' => $toleranceDays],
        );
    }

    private function makeStrategy(array $lines): DepartmentsBonusOnTimeMetricStrategy
    {
        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->method('search')->willReturn(new class ($lines) {
            public function __construct(private array $lines)
            {
            }

            public function getResult(): array
            {
                return $this->lines;
            }
        });

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        return new DepartmentsBonusOnTimeMetricStrategy($repo, $security, $this->makeWorkingDayCalculator());
    }

    /**
     * Kalendarz bez firmowych wyjątków — zostają weekendy i święta ustawowe.
     */
    private function makeWorkingDayCalculator(): WorkingDayCalculator
    {
        $scheduleRepo = $this->createMock(WorkScheduleRepository::class);
        $scheduleRepo->method('findHolidaysByRange')->willReturn([]);
        $scheduleRepo->method('findWorkingDaysByRange')->willReturn([]);

        return new WorkingDayCalculator(
            new WorkScheduleService($scheduleRepo, new DefaultHolidaysProvider())
        );
    }

    private function prod(
        string $slug,
        ?\DateTimeInterface $dateStart,
        ?\DateTimeInterface $dateEnd,
        ?\DateTimeInterface $completedAt,
        ?AssembledFactorDTO $bonus = null,
    ): ProductionRM {
        return new ProductionRM(
            departmentSlug: $slug,
            id: random_int(1, 100000),
            dateStart: $dateStart,
            dateEnd: $dateEnd,
            status: (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            isCompleted: true,
            completedAt: $completedAt,
            factorRatio: null,
            factorBonus: $bonus,
            isGhost: false,
        );
    }

    private function makeLine(int $id, array $productions): AgreementLineRM
    {
        $line = new AgreementLineRM($id);
        $line->setOrderNumber('ORD-' . $id);
        $line->setConfirmedDate(new \DateTime('2026-05-01'));
        $line->setCustomer(new CustomerRM($id, 'Klient ' . $id));
        $line->setProductName('Produkt ' . $id);
        $line->setFactor(1.0);
        $line->setProductionStartDate(null);
        $line->setProductionEndDate(null);
        $line->setProductions($productions);

        return $line;
    }
}
