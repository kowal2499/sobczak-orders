<?php

namespace App\Tests\Unit\Modules\BonusSettlement;

use App\Module\BonusSettlement\Service\DepartmentFactorsCalculator;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Module\Reports\Production\Metric\MetricStrategyInterface;
use App\Module\Reports\Production\Provider\DashboardMetricProvider;
use PHPUnit\Framework\TestCase;

class DepartmentFactorsCalculatorTest extends TestCase
{
    public function testShouldSumFactorsPerDepartment(): void
    {
        // Given
        $calculator = $this->makeCalculator([
            $this->record('dpt04', 84.0),
            $this->record('dpt04', 31.5),
            $this->record('dpt03', 62.0),
        ]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(115.5, $sums['dpt04']);
        $this->assertSame(62.0, $sums['dpt03']);
    }

    public function testShouldReportEveryProductionDepartment(): void
    {
        // Given
        $calculator = $this->makeCalculator([$this->record('dpt04', 84.0)]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(
            ['dpt01', 'dpt02', 'dpt06', 'dpt03', 'dpt04', 'dpt05'],
            array_keys($sums)
        );
        $this->assertSame(0.0, $sums['dpt01']);
    }

    public function testShouldSkipLateProduction(): void
    {
        // Given
        $calculator = $this->makeCalculator([
            $this->record('dpt04', 84.0),
            $this->record('dpt04', 50.0, onTime: false),
        ]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(84.0, $sums['dpt04']);
    }

    public function testShouldSkipRecordOutOfReportRange(): void
    {
        // Given
        $calculator = $this->makeCalculator([
            $this->record('dpt04', 84.0),
            $this->record('dpt04', 50.0, inRange: false),
        ]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(84.0, $sums['dpt04']);
    }

    public function testShouldSkipGhost(): void
    {
        // Given
        $calculator = $this->makeCalculator([
            $this->record('dpt04', 84.0),
            $this->record('dpt04', 50.0, isGhost: true),
        ]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(84.0, $sums['dpt04']);
    }

    public function testShouldSkipRecordWithoutFactors(): void
    {
        // Given
        $calculator = $this->makeCalculator([
            $this->record('dpt04', 84.0),
            new ProductionReportRecordDTO(departmentSlug: 'dpt04', factors: null),
        ]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertSame(84.0, $sums['dpt04']);
    }

    public function testShouldIgnoreUnknownDepartment(): void
    {
        // Given
        $calculator = $this->makeCalculator([$this->record('custom_task', 10.0)]);

        // When
        $sums = $this->calculate($calculator);

        // Then
        $this->assertArrayNotHasKey('custom_task', $sums);
        $this->assertSame(0.0, array_sum($sums));
    }

    public function testShouldPassToleranceToMetric(): void
    {
        // Given
        $strategy = $this->strategy([]);
        $calculator = new DepartmentFactorsCalculator(new DashboardMetricProvider([$strategy]));

        // When
        $calculator->forRange(new \DateTime('2026-08-01'), new \DateTime('2026-08-31'), 3);

        // Then
        $this->assertSame(3, $strategy->receivedOptions['toleranceDays']);
    }

    /**
     * Reguła sumowania żyje w dwóch miejscach: tutaj i w mixinie pulpitu. Rozjazd oznacza,
     * że kafel pokazuje inną premię niż rozliczenie, a zauważa się to dopiero przy wypłacie.
     * Ten test pilnuje, żeby zmiana po stronie frontu nie przeszła niezauważona.
     */
    public function testFrontRuleShouldStillMatchThisImplementation(): void
    {
        // Given
        $mixin = file_get_contents(
            __DIR__
            . '/../../../../assets/js-vue/src/modules/dashboard/components/Metrics'
            . '/ProductionMetric/DepartmentMetricMixin.js'
        );
        $this->assertIsString($mixin, 'Nie znaleziono DepartmentMetricMixin.js');

        // When
        $normalized = preg_replace('/\s+/', ' ', $mixin);

        // Then
        $this->assertStringContainsString(
            'item.departmentSlug === department.slug && item.onTime !== false && item.inRange !== false',
            $normalized,
            'Reguła sumowania na froncie zmieniła się - zaktualizuj DepartmentFactorsCalculator'
        );
    }

    /**
     * @param ProductionReportRecordDTO[] $records
     */
    private function makeCalculator(array $records): DepartmentFactorsCalculator
    {
        return new DepartmentFactorsCalculator(new DashboardMetricProvider([$this->strategy($records)]));
    }

    /**
     * @return array<string, float>
     */
    private function calculate(DepartmentFactorsCalculator $calculator): array
    {
        return $calculator->forRange(new \DateTime('2026-08-01'), new \DateTime('2026-08-31'), 5);
    }

    private function record(
        string $slug,
        float $factor,
        bool $onTime = true,
        bool $inRange = true,
        bool $isGhost = false
    ): ProductionReportRecordDTO {
        return new ProductionReportRecordDTO(
            departmentSlug: $slug,
            factors: new AssembledFactorDTO($factor),
            isGhost: $isGhost,
            onTime: $onTime,
            inRange: $inRange,
        );
    }

    /**
     * @param ProductionReportRecordDTO[] $records
     */
    private function strategy(array $records): MetricStrategyInterface
    {
        return new class ($records) implements MetricStrategyInterface {
            public array $receivedOptions = [];

            public function __construct(private readonly array $records)
            {
            }

            public function getName(): string
            {
                return DepartmentFactorsCalculator::METRIC;
            }

            public function compute(
                ?\DateTimeInterface $start,
                ?\DateTimeInterface $end,
                bool $includeGhost = false,
                array $options = []
            ): array {
                $this->receivedOptions = $options;

                return $this->records;
            }
        };
    }
}
