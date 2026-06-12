<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\AgreementLine;
use App\Entity\Customer;

/**
 * Charakteryzuje obecne zachowanie GET /reports/production/agreement-line-production-summary
 * (mierniki "Orders Pending" / "Orders Finished").
 *
 * Reguły utrwalone z DoctrineProductionPendingRepository / DoctrineProductionFinishedRepository:
 *  - Pending:  productionCompletionDate IS NULL AND deleted=0 AND productionStartDate <= end (23:59:59).
 *              UWAGA: supplier woła getSummary(null, $end) — dolna granica (start) jest ignorowana.
 *  - Finished: productionStartDate IS NOT NULL AND deleted=0
 *              AND productionCompletionDate BETWEEN start(00:00) AND end(23:59:59);
 *              dla ROLE_CUSTOMER dodatkowo filtr po przypisanych klientach.
 */
class AgreementLineProductionSummaryTest extends BaseProductionReportsTestCase
{
    private const URL = '/reports/production/agreement-line-production-summary';

    public function testShouldReturnZeroCountsWhenNoData(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertNull($content['orders_pending']['factors_summary']);
        $this->assertSame(0, (int) $content['orders_pending']['count']);
        $this->assertNull($content['orders_finished']['factors_summary']);
        $this->assertSame(0, (int) $content['orders_finished']['count']);
    }

    public function testPendingCountsLinesStartedBeforeEndWithoutCompletion(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // Liczy się: start w oknie, brak completion
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-10'),
            productionCompletionDate: null,
            factor: 2.0,
        );
        // Liczy się: start PRZED oknem (Pending ignoruje dolną granicę), brak completion
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-04-01'),
            productionCompletionDate: null,
            factor: 3.0,
        );
        // Nie liczy się: ma completionDate
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-12'),
            productionCompletionDate: new \DateTime('2026-05-20'),
            factor: 5.0,
        );
        // Nie liczy się: start PO oknie
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-06-15'),
            productionCompletionDate: null,
            factor: 7.0,
        );
        // Nie liczy się: usunięta
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-05'),
            productionCompletionDate: null,
            deleted: true,
            factor: 11.0,
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, (int) $content['orders_pending']['count']);
        $this->assertSame(5.0, (float) $content['orders_pending']['factors_summary']);
    }

    public function testFinishedCountsLinesCompletedInRange(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // Liczy się: completion w oknie, start ustawiony
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-15'),
            factor: 4.0,
        );
        // Nie liczy się: completion przed oknem
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-04-01'),
            productionCompletionDate: new \DateTime('2026-04-20'),
            factor: 6.0,
        );
        // Nie liczy się: completion po oknie
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-06-10'),
            factor: 8.0,
        );
        // Nie liczy się: brak productionStartDate
        $this->makeAgreementLine(
            productionStartDate: null,
            productionCompletionDate: new \DateTime('2026-05-18'),
            factor: 9.0,
        );
        // Nie liczy się: usunięta
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-02'),
            productionCompletionDate: new \DateTime('2026-05-19'),
            deleted: true,
            factor: 13.0,
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, (int) $content['orders_finished']['count']);
        $this->assertSame(4.0, (float) $content['orders_finished']['factors_summary']);
    }

    public function testFinishedRespectsRangeBoundaries(): void
    {
        // Given — completion dokładnie na granicach okna (00:00 startu i 23:59:59 końca)
        $client = $this->login($this->createUser());

        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-01 00:00:00'),
            factor: 1.0,
        );
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-31 23:59:59'),
            factor: 1.0,
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, (int) $content['orders_finished']['count']);
    }

    public function testFinishedFiltersByOwnedCustomersForRoleCustomer(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_CUSTOMER']);
        $customerOwned = $this->factory->make(Customer::class);
        $customerOther = $this->factory->make(Customer::class);
        $this->factory->flush();
        $user->addCustomer($customerOwned);
        $this->factory->flush();

        $client = $this->login($user);

        $this->makeAgreementLine(
            customer: $customerOwned,
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-10'),
            factor: 4.0,
        );
        $this->makeAgreementLine(
            customer: $customerOther,
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-11'),
            factor: 6.0,
        );

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-01&end=2026-05-31');

        // Then — Finished filtruje po właścicielu (tylko klient przypisany)
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, (int) $content['orders_finished']['count']);
        $this->assertSame(4.0, (float) $content['orders_finished']['factors_summary']);
    }

    public function testShouldReturn400WhenDatesMissing(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL);

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturn400WhenStartAfterEnd(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->xmlHttpRequest('GET', self::URL . '?start=2026-05-31&end=2026-05-01');

        // Then
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }
}
