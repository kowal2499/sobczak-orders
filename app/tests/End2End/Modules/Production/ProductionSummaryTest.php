<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use App\Module\WorkConfiguration\Entity\WorkCapacity;
use App\Tests\End2End\Modules\Reports\Production\BaseProductionReportsTestCase;

/**
 * Charakteryzuje obecne zachowanie POST /production/summary (blok produkcji + firstFreeDay).
 *
 * Reguły utrwalone z ProductionRepository::getNotCompletedAgreementLines() / getCompletedAgreementLines():
 *  - liczone tylko produkcje działu dpt05,
 *  - "w toku": isGhost=0, createdAt <= ostatni dzień miesiąca, status != '3',
 *             AgreementLine.status IN (WAITING, MANUFACTURING), deleted=0,
 *  - "zakończone": isGhost=0, status='3', StatusLog.currentStatus='3' z createdAt w obrębie miesiąca,
 *             AgreementLine.deleted=0, status NOT IN (DELETED),
 *  - factorLimit = floor(capacity * workingDays),
 *  - dla ROLE_CUSTOMER zarówno ordersInProduction jak i factorsInProduction są filtrowane po
 *    przypisanych klientach (współdzielony, mutowany QueryBuilder — patrz test poniżej).
 */
class ProductionSummaryTest extends BaseProductionReportsTestCase
{
    private const URL = '/production/summary';
    private const MONTH = 6;
    private const YEAR = 2026;

    public function testShouldReturnZeroProductionWhenNoData(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->createCapacity(new \DateTime('2026-06-01'), 2.0);
        $this->factory->flush();

        // When
        $client->request('POST', self::URL, ['month' => self::MONTH, 'year' => self::YEAR]);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(0, $content['production']['ordersInProduction']);
        $this->assertSame(0, $content['production']['ordersFinished']);
        $this->assertSame(0, (int) $content['production']['factorsInProduction']);
        $this->assertSame(0, (int) $content['production']['factorsFinished']);

        $this->assertGreaterThan(0, $content['workingDays']);
        $this->assertSame(floor(2.0 * $content['workingDays']), (float) $content['factorLimit']);
        // Brak produkcji w toku → firstFreeDay = dzisiaj
        $this->assertSame((new \DateTime())->format('Y-m-d'), $content['firstFreeDay']);
    }

    public function testShouldCountInProductionLines(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->createCapacity(new \DateTime('2026-06-01'), 2.0);

        $line = $this->makeDpt05Line(
            factor: 4.0,
            productionStatus: TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            alStatus: AgreementLine::STATUS_MANUFACTURING,
        );
        // Nie liczy się: dział inny niż dpt05
        $this->makeAgreementLine(
            status: AgreementLine::STATUS_MANUFACTURING,
            productions: [['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING, 'status' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED]],
        );
        // Nie liczy się: AgreementLine w statusie spoza (WAITING, MANUFACTURING)
        $this->makeDpt05Line(
            factor: 9.0,
            productionStatus: TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            alStatus: AgreementLine::STATUS_WAREHOUSE,
        );

        // When
        $client->request('POST', self::URL, ['month' => self::MONTH, 'year' => self::YEAR]);

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $content['production']['ordersInProduction']);
        $this->assertSame(4.0, (float) $content['production']['factorsInProduction']);
        $this->assertSame([$line->getId()], $content['production']['pendingIds']);
        // firstFreeDay wyznaczony w przyszłości (>= dzisiaj)
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $content['firstFreeDay']);
        $this->assertGreaterThanOrEqual((new \DateTime())->format('Y-m-d'), $content['firstFreeDay']);
    }

    public function testShouldExcludeGhostFromInProduction(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->createCapacity(new \DateTime('2026-06-01'), 2.0);
        $this->makeDpt05Line(
            factor: 4.0,
            productionStatus: TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            alStatus: AgreementLine::STATUS_MANUFACTURING,
            isGhost: true,
        );

        // When
        $client->request('POST', self::URL, ['month' => self::MONTH, 'year' => self::YEAR]);

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $content['production']['ordersInProduction']);
    }

    public function testShouldCountFinishedLines(): void
    {
        // Given
        $client = $this->login($this->createUser());
        $this->createCapacity(new \DateTime('2026-06-01'), 2.0);

        $line = $this->makeDpt05Finished(
            factor: 5.0,
            logDate: new \DateTime('2026-06-15'),
        );
        // Nie liczy się: zakończenie poza miesiącem
        $this->makeDpt05Finished(
            factor: 7.0,
            logDate: new \DateTime('2026-05-15'),
        );

        // When
        $client->request('POST', self::URL, ['month' => self::MONTH, 'year' => self::YEAR]);

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $content['production']['ordersFinished']);
        $this->assertSame(5.0, (float) $content['production']['factorsFinished']);
        $this->assertSame([$line->getId()], $content['production']['finishedIds']);
    }

    /**
     * UWAGA — utrwala faktyczne zachowanie: withConnectedCustomers() mutuje współdzielony
     * QueryBuilder, więc dla ROLE_CUSTOMER zarówno ordersInProduction, jak i factorsInProduction
     * są filtrowane po przypisanych klientach (mimo komentarza "bez połączonych klientów" w kodzie).
     */
    public function testRoleCustomerFiltersBothOrdersAndFactors(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_CUSTOMER']);
        $owned = $this->factory->make(Customer::class);
        $other = $this->factory->make(Customer::class);
        $this->factory->flush();
        $user->addCustomer($owned);
        $this->factory->flush();

        $client = $this->login($user);
        $this->createCapacity(new \DateTime('2026-06-01'), 2.0);

        $this->makeDpt05Line(factor: 4.0, productionStatus: TaskTypes::TYPE_DEFAULT_STATUS_STARTED, alStatus: AgreementLine::STATUS_MANUFACTURING, customer: $owned);
        $this->makeDpt05Line(factor: 6.0, productionStatus: TaskTypes::TYPE_DEFAULT_STATUS_STARTED, alStatus: AgreementLine::STATUS_MANUFACTURING, customer: $other);

        // When
        $client->request('POST', self::URL, ['month' => self::MONTH, 'year' => self::YEAR]);

        // Then — oba filtrowane po przypisanym kliencie (współdzielony QueryBuilder)
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $content['production']['ordersInProduction']);
        $this->assertSame(4.0, (float) $content['production']['factorsInProduction']);
    }

    private function makeDpt05Line(
        float $factor,
        string $productionStatus,
        int $alStatus,
        bool $isGhost = false,
        ?Customer $customer = null,
    ): AgreementLine {
        return $this->makeAgreementLine(
            customer: $customer,
            factor: $factor,
            status: $alStatus,
            productions: [[
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING,
                'status' => $productionStatus,
                'isGhost' => $isGhost,
            ]],
        );
    }

    private function makeDpt05Finished(float $factor, \DateTimeInterface $logDate): AgreementLine
    {
        $line = $this->makeAgreementLine(
            factor: $factor,
            status: AgreementLine::STATUS_MANUFACTURING,
            productions: [[
                'slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING,
                'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                'isCompleted' => true,
            ]],
        );

        /** @var Production $production */
        $production = $line->getProductions()->first();
        $log = $this->factory->make(StatusLog::class, ['createdAt' => $logDate]);
        $log->setCurrentStatus((string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);
        $log->setProduction($production);
        $production->addStatusLog($log);
        $this->factory->flush();

        return $line;
    }

    private function createCapacity(\DateTimeInterface $dateFrom, float $capacity): void
    {
        $this->getManager()->persist(new WorkCapacity($dateFrom, $capacity));
    }
}
