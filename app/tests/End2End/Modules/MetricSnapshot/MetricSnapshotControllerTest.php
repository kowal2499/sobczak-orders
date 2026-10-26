<?php

namespace App\Tests\End2End\Modules\MetricSnapshot;

use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\MetricSnapshot\Command\CaptureMetricSnapshotCommand;
use App\Module\MetricSnapshot\Entity\MetricSnapshot;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use App\System\CommandBus;
use App\Tests\End2End\Modules\Reports\Production\BaseProductionReportsTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Baza testowa może mieć własne dane, więc asercje dotyczą wierszy klientów założonych w teście,
 * a zgodność z pulpitem sprawdzamy porównaniem z kaflem liczonym na żywo w tym samym stanie.
 */
class MetricSnapshotControllerTest extends BaseProductionReportsTestCase
{
    private const CAPTURE_URL = '/metric-snapshot/capture';
    private const HISTORY_URL = '/metric-snapshot/history';

    public function testShouldSaveRowsPerCustomer(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.manage']));
        $customer = $this->makeCustomer();
        $this->finishedLine($customer, 4.0);
        $this->finishedLine($customer, 2.5);
        $this->pendingLine($customer, 3.0);

        // When
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $finished = $this->rowFor(SnapshotMetric::ORDERS_FINISHED, $customer);
        $this->assertSame(6.5, $finished->getFactorSum());
        $this->assertSame(2, $finished->getItemCount());
        $this->assertSame(SnapshotSource::MANUAL, $finished->getSource());
        $this->assertSame(3.0, $this->rowFor(SnapshotMetric::ORDERS_PENDING, $customer)->getFactorSum());
    }

    public function testShouldReplaceTodayInsteadOfDuplicating(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.manage']));
        $customer = $this->makeCustomer();
        $this->finishedLine($customer, 4.0);
        $client->request('POST', self::CAPTURE_URL);
        $rowsAfterFirst = $this->countToday();

        // When
        $this->finishedLine($customer, 1.0);
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $this->assertSame($rowsAfterFirst, $this->countToday());
        $this->assertSame(5.0, $this->rowFor(SnapshotMetric::ORDERS_FINISHED, $customer)->getFactorSum());
    }

    public function testShouldSaveForeignCustomersWhenCapturedByRoleCustomer(): void
    {
        // Given
        $owned = $this->makeCustomer();
        $foreign = $this->makeCustomer();
        $user = $this->customerUser([$owned], ['metric-snapshot.manage']);
        $client = $this->login($user);
        $this->finishedLine($owned, 4.0);
        $this->finishedLine($foreign, 6.0);

        // When
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(6.0, $this->rowFor(SnapshotMetric::ORDERS_FINISHED, $foreign)->getFactorSum());
    }

    public function testShouldSplitOnTimeAndLateFactorsPerDepartment(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.manage']));
        $customer = $this->makeCustomer();
        $this->completedProduction($customer, onTime: true);
        $this->completedProduction($customer, onTime: false);

        // When
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $in = $this->rowFor(SnapshotMetric::ON_TIME_IN, $customer, TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $late = $this->rowFor(SnapshotMetric::ON_TIME_LATE, $customer, TaskTypes::TYPE_DEFAULT_SLUG_GRINDING);
        $this->assertSame(1, $in->getItemCount());
        $this->assertSame(1, $late->getItemCount());
        $this->assertSame(['toleranceDays' => 5], $in->getParams());
    }

    public function testHistoryShouldMatchLiveTileForRoleCustomer(): void
    {
        // Given
        $owned = $this->makeCustomer();
        $foreign = $this->makeCustomer();
        $user = $this->customerUser([$owned], ['metric-snapshot.manage', 'metric-snapshot.view'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        $this->finishedLine($owned, 4.0);
        $this->finishedLine($foreign, 6.0);
        $this->pendingLine($owned, 2.0);
        $this->pendingLine($foreign, 9.0);
        $client->request('POST', self::CAPTURE_URL);

        // When
        $history = $this->history($client);
        $client->xmlHttpRequest('GET', sprintf(
            '/reports/production/agreement-line-production-summary?start=%s&end=%s',
            $this->monthStart(),
            $this->today()
        ));
        $live = json_decode($client->getResponse()->getContent(), true);

        // Then
        $finished = $this->todayPoint($history, SnapshotMetric::ORDERS_FINISHED, 'total');
        $pending = $this->todayPoint($history, SnapshotMetric::ORDERS_PENDING, 'total');
        // json_decode zamienia 4.0 na 4
        $this->assertSame(4.0, (float) $finished['factorSum']);
        $this->assertSame((float) $live['orders_finished']['factors_summary'], (float) $finished['factorSum']);
        $this->assertSame((int) $live['orders_finished']['count'], $finished['itemCount']);
        $this->assertSame((float) $live['orders_pending']['factors_summary'], (float) $pending['factorSum']);
    }

    public function testHistoryShouldHideDepartmentsWithoutGrant(): void
    {
        // Given
        $customer = $this->makeCustomer();
        $user = $this->createUser([], [], [
            'metric-snapshot.manage',
            'metric-snapshot.view',
            'reports.dashboard:on-time-bonus',
            'production.show.cnc',
        ]);
        $client = $this->login($user);
        $this->completedProduction($customer, onTime: true);
        $client->request('POST', self::CAPTURE_URL);

        // When
        $history = $this->history($client);

        // Then
        $dimensions = array_column(array_filter(
            $history['series'],
            fn (array $s) => $s['metric'] === SnapshotMetric::ON_TIME_IN->value
        ), 'dimension');
        $this->assertNotContains(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, $dimensions);
    }

    public function testHistoryShouldHideMetricsWithoutTileGrant(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.manage', 'metric-snapshot.view']));
        $this->completedProduction($this->makeCustomer(), onTime: true);
        $client->request('POST', self::CAPTURE_URL);

        // When
        $history = $this->history($client);

        // Then
        $this->assertSame([], $history['series'], 'bez grantów kafli i ROLE_PRODUCTION nic nie widać');
        $this->assertNotNull($history['computedAt']);
    }

    public function testShouldRefuseOverwritingPastDay(): void
    {
        // Then
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('zamrożony');

        // When
        $this->commandBus()->dispatch(new CaptureMetricSnapshotCommand(
            (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            SnapshotSource::CRON,
        ));
    }

    public function testShouldBackfillPastDayOnlyOnce(): void
    {
        // Given
        $day = (new \DateTimeImmutable('-400 days'))->format('Y-m-d');
        $this->getManager()->persist(new MetricSnapshot(
            new \DateTimeImmutable($day),
            SnapshotMetric::ORDERS_FINISHED,
            MetricSnapshot::TOTAL,
            MetricSnapshot::NO_PERIOD,
            1,
            1.0,
            1,
            null,
            new \DateTimeImmutable(),
            SnapshotSource::CRON,
        ));
        $this->getManager()->flush();

        // Then
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('ma już zapis');

        // When
        $this->commandBus()->dispatch(new CaptureMetricSnapshotCommand($day, SnapshotSource::BACKFILL));
    }

    public function testBackfillShouldSkipPresentStateMetrics(): void
    {
        // Given
        $day = new \DateTimeImmutable('-401 days');
        $this->getManager()->createQuery('DELETE FROM ' . MetricSnapshot::class . ' s WHERE s.snapshotDate = :day')
            ->setParameter('day', $day->format('Y-m-d'))
            ->execute();

        // When
        $this->commandBus()->dispatch(new CaptureMetricSnapshotCommand($day->format('Y-m-d'), SnapshotSource::BACKFILL));

        // Then
        $rows = $this->getManager()->getRepository(MetricSnapshot::class)->findBy(['snapshotDate' => $day]);
        foreach ($rows as $row) {
            $this->assertTrue($row->getMetric()->isFlow(), $row->getMetric()->value . ' nie powinien trafić do backfillu');
            $this->assertSame(SnapshotSource::BACKFILL, $row->getSource());
        }
    }

    public function testShouldLogManualCapture(): void
    {
        // Given
        $user = $this->createUser([], [], ['metric-snapshot.manage']);
        $client = $this->login($user);

        // When
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $log = $this->getManager()->getRepository(ActivityLog::class)->findOneBy(
            ['type' => 'metric_snapshot.captured'],
            ['id' => 'DESC']
        );
        $this->assertNotNull($log);
        $this->assertSame($user->getId(), $log->getUser()?->getId());
    }

    public function testShouldDenyCaptureWithoutGrant(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.view']));

        // When
        $client->request('POST', self::CAPTURE_URL);

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldDenyHistoryWithoutGrant(): void
    {
        // Given
        $client = $this->login($this->createUser([], [], ['metric-snapshot.manage']));

        // When
        $client->request('GET', self::HISTORY_URL . '?from=2026-10-01&to=2026-10-31');

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    private function makeCustomer(): Customer
    {
        $customer = $this->factory->make(Customer::class);
        $this->factory->flush();

        return $customer;
    }

    /**
     * @param Customer[] $customers
     * @param string[] $grants
     * @param string[] $extraRoles
     */
    private function customerUser(array $customers, array $grants, array $extraRoles = []): User
    {
        $user = $this->createUser([], [], $grants, array_merge(['ROLE_CUSTOMER'], $extraRoles));
        foreach ($customers as $customer) {
            $user->addCustomer($customer);
        }
        $this->factory->flush();

        return $user;
    }

    private function finishedLine(Customer $customer, float $factor): void
    {
        $this->makeAgreementLine(
            customer: $customer,
            productionStartDate: new \DateTime($this->monthStart()),
            productionCompletionDate: new \DateTime('today 08:00'),
            factor: $factor,
        );
    }

    private function pendingLine(Customer $customer, float $factor): void
    {
        $this->makeAgreementLine(
            customer: $customer,
            productionStartDate: new \DateTime($this->monthStart()),
            factor: $factor,
        );
    }

    /**
     * Okno produkcji wokół dziś (w terminie) albo dwa miesiące temu (po terminie także z widełkami).
     */
    private function completedProduction(Customer $customer, bool $onTime): void
    {
        $windowEnd = $onTime ? new \DateTime('today') : new \DateTime('-2 months');
        $this->makeAgreementLine(
            customer: $customer,
            productionStartDate: new \DateTime($this->monthStart()),
            productions: [[
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_GRINDING,
                'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                'isCompleted' => true,
                'completedAt' => new \DateTime('today 08:00'),
                'dateStart' => (clone $windowEnd)->modify('-3 days'),
                'dateEnd' => $windowEnd,
            ]],
        );
    }

    private function rowFor(SnapshotMetric $metric, Customer $customer, string $dimension = MetricSnapshot::TOTAL): MetricSnapshot
    {
        $this->getManager()->clear();
        $row = $this->getManager()->getRepository(MetricSnapshot::class)->findOneBy([
            'snapshotDate' => new \DateTimeImmutable('today'),
            'metric' => $metric,
            'dimension' => $dimension,
            'customerId' => $customer->getId(),
        ]);
        $this->assertNotNull($row, sprintf('brak wiersza %s/%s dla klienta', $metric->value, $dimension));

        return $row;
    }

    private function countToday(): int
    {
        return count($this->getManager()->getRepository(MetricSnapshot::class)->findBy([
            'snapshotDate' => new \DateTimeImmutable('today'),
        ]));
    }

    /**
     * @return array{computedAt: ?string, series: array<int, array<string, mixed>>}
     */
    private function history(KernelBrowser $client): array
    {
        $client->request('GET', sprintf('%s?from=%s&to=%s', self::HISTORY_URL, $this->monthStart(), $this->today()));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * @param array{series: array<int, array<string, mixed>>} $history
     * @return array{date: string, factorSum: ?float, itemCount: int, source: string}
     */
    private function todayPoint(array $history, SnapshotMetric $metric, string $dimension): array
    {
        foreach ($history['series'] as $series) {
            if ($series['metric'] !== $metric->value || $series['dimension'] !== $dimension) {
                continue;
            }
            foreach ($series['points'] as $point) {
                if ($point['date'] === $this->today()) {
                    return $point;
                }
            }
        }
        $this->fail(sprintf('brak punktu %s/%s na dziś', $metric->value, $dimension));
    }

    private function commandBus(): CommandBus
    {
        return $this->get(CommandBus::class);
    }

    private function monthStart(): string
    {
        return (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');
    }

    private function today(): string
    {
        return (new \DateTimeImmutable('today'))->format('Y-m-d');
    }
}
