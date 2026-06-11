<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;

/**
 * Charakteryzuje obecne zachowanie endpointów szczegółów (drill-down):
 *  - GET /reports/production/production-pending-details
 *  - GET /reports/production/production-finished-details
 *
 * Reguły utrwalone z DoctrineProductionPendingRepository::getDetails() /
 * DoctrineProductionFinishedRepository::getDetails() (+ BaseSupplier::transformRows):
 *  - Pending:  productionCompletionDate IS NULL AND deleted=0 AND productionStartDate <= end.
 *              UWAGA: getRecords woła getDetails(null, $end) — dolna granica jest ignorowana.
 *  - Finished: productionStartDate IS NOT NULL AND deleted=0
 *              AND productionCompletionDate BETWEEN start AND end; filtr ROLE_CUSTOMER.
 *  - Rekordy powstają per kwalifikująca się produkcja (leftJoin: departmentSlug w działach
 *    domyślnych, isGhost=0, status w [COMPLETED, NOT_APPLICABLE] dla Pending / =COMPLETED dla
 *    Finished). Linia bez pasującej produkcji daje JEDEN rekord z departmentSlug=''.
 *  - dateStart/dateEnd/status w rekordach details są null (kolumny nieselekcjonowane);
 *    completedAt pochodzi z produkcji; factors liczone wg FACTOR_ADJUSTMENT_RATIO.
 */
class ProductionDetailsTest extends BaseProductionReportsTestCase
{
    private const PENDING_URL = '/reports/production/production-pending-details';
    private const FINISHED_URL = '/reports/production/production-finished-details';

    public function testPendingReturnsEmptyWhenNoData(): void
    {
        $client = $this->login($this->createUser());

        $client->xmlHttpRequest('GET', self::PENDING_URL . '?end=2026-05-31');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testPendingRecordPerQualifyingProduction(): void
    {
        $client = $this->login($this->createUser());

        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-10'),
            productionCompletionDate: null,
            factor: 2.0,
            confirmedDate: new \DateTime('2026-05-01'),
            productions: [
                ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING, 'status' => (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, 'isCompleted' => true, 'completedAt' => new \DateTime('2026-05-09')],
                ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_CNC, 'status' => (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, 'isCompleted' => true, 'completedAt' => new \DateTime('2026-05-08')],
            ],
        );

        $client->xmlHttpRequest('GET', self::PENDING_URL . '?end=2026-05-31');

        $records = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $records);
        $slugs = array_column($records, 'departmentSlug');
        sort($slugs);
        $this->assertSame(['dpt01', 'dpt02'], $slugs);
        // Pola produkcyjne nieselekcjonowane w details
        $this->assertNull($records[0]['dateStart']);
        $this->assertNull($records[0]['dateEnd']);
        $this->assertNull($records[0]['status']);
        // factor z linii
        $this->assertSame(2.0, (float) $records[0]['factors']['factor']);
        $this->assertSame(2.0, (float) $records[0]['agreementLine']['factor']);
    }

    public function testPendingLineWithoutMatchingProductionYieldsSingleEmptyDeptRecord(): void
    {
        $client = $this->login($this->createUser());

        // produkcja ghost — wykluczona z leftJoin, więc brak dopasowania => jeden rekord z pustym działem
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-10'),
            productionCompletionDate: null,
            factor: 3.0,
            productions: [
                ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING, 'status' => (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, 'isCompleted' => true, 'completedAt' => new \DateTime('2026-05-09'), 'isGhost' => true],
            ],
        );

        $client->xmlHttpRequest('GET', self::PENDING_URL . '?end=2026-05-31');

        $records = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $records);
        $this->assertSame('', $records[0]['departmentSlug']);
        $this->assertNull($records[0]['completedAt']);
        $this->assertSame(3.0, (float) $records[0]['factors']['factor']);
    }

    public function testPendingExcludesCompletedAfterEndAndDeleted(): void
    {
        $client = $this->login($this->createUser());

        // ma completionDate => wykluczona
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-05'),
            productionCompletionDate: new \DateTime('2026-05-20'),
        );
        // start po końcu zakresu => wykluczona
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-06-15'),
            productionCompletionDate: null,
        );
        // usunięta => wykluczona
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-05'),
            productionCompletionDate: null,
            deleted: true,
        );
        // kwalifikuje się (start przed zakresem — dolna granica ignorowana)
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-04-01'),
            productionCompletionDate: null,
        );

        $client->xmlHttpRequest('GET', self::PENDING_URL . '?end=2026-05-31');

        $records = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $records);
    }

    public function testFinishedReturnsEmptyWhenNoData(): void
    {
        $client = $this->login($this->createUser());

        $client->xmlHttpRequest('GET', self::FINISHED_URL . '?start=2026-05-01&end=2026-05-31');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testFinishedRecordsForLinesCompletedInRange(): void
    {
        $client = $this->login($this->createUser());

        // kwalifikuje się: completion w zakresie, start ustawiony, produkcja COMPLETED
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-15'),
            factor: 4.0,
            productions: [
                ['slug' => TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, 'status' => (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, 'isCompleted' => true, 'completedAt' => new \DateTime('2026-05-15')],
            ],
        );
        // completion poza zakresem => wykluczona
        $this->makeAgreementLine(
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-06-10'),
        );
        // brak productionStartDate => wykluczona
        $this->makeAgreementLine(
            productionStartDate: null,
            productionCompletionDate: new \DateTime('2026-05-18'),
        );

        $client->xmlHttpRequest('GET', self::FINISHED_URL . '?start=2026-05-01&end=2026-05-31');

        $records = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $records);
        $this->assertSame('dpt05', $records[0]['departmentSlug']);
        $this->assertSame(4.0, (float) $records[0]['agreementLine']['factor']);
    }

    public function testFinishedFiltersByOwnedCustomersForRoleCustomer(): void
    {
        $user = $this->createUser([], [], [], ['ROLE_CUSTOMER']);
        $owned = $this->factory->make(Customer::class);
        $other = $this->factory->make(Customer::class);
        $this->factory->flush();
        $user->addCustomer($owned);
        $this->factory->flush();

        $client = $this->login($user);

        $this->makeAgreementLine(
            customer: $owned,
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-10'),
            factor: 4.0,
        );
        $this->makeAgreementLine(
            customer: $other,
            productionStartDate: new \DateTime('2026-05-01'),
            productionCompletionDate: new \DateTime('2026-05-11'),
            factor: 6.0,
        );

        $client->xmlHttpRequest('GET', self::FINISHED_URL . '?start=2026-05-01&end=2026-05-31');

        $records = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $records);
        $this->assertSame(4.0, (float) $records[0]['agreementLine']['factor']);
    }
}
