<?php

namespace App\Tests\End2End\Modules\Reports;

use App\Entity\User;
use App\Modules\Reports\Production\ProductionReport;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ReportsControllerTest extends ApiTestCase
{
    private $reportUnderTest;
    /** @var AgreementLineFixtureHelpers */
    private $agreementLineFixturesHelper;
    /** @var AgreementLineChainFactory */
    private $agreementLineChainFactory;
    /** @var User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $manager = $this->getManager();
        $factory = new EntityFactory($manager);
        $this->user = $factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);
        $manager->flush();
        $this->client = $this->login($this->user);

        $this->agreementLineChainFactory = new AgreementLineChainFactory($factory);
        $this->agreementLineFixturesHelper = new AgreementLineFixtureHelpers(
            $factory,
            $this->agreementLineChainFactory
        );
        /** @var DoctrineProductionPendingRepository $pendingRepository */
        $pendingRepository = $this->get(DoctrineProductionPendingRepository::class);
        /** @var DoctrineProductionFinishedRepository $finishedRepository */
        $finishedRepository = $this->get(DoctrineProductionFinishedRepository::class);

        $this->reportUnderTest = new ProductionReport(
            $pendingRepository,
            $finishedRepository
        );
    }

    public function testShouldGetResponseFromAgreementLineProductionSummary()
    {
        // Given
        $dateStart = '2021-09-01';
        $dateEnd = '2021-09-30';

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-08-20'), 'productionCompletionDate' => new \DateTime('2021-08-30')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-10'), 'productionCompletionDate' => new \DateTime('2021-09-15')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-10'), 'productionCompletionDate' => new \DateTime('2021-09-20')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-10-10'), 'productionCompletionDate' => new \DateTime('2021-10-21')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-02-20')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-10-10')]);
        // When
        $this->client->xmlHttpRequest('GET', '/api/reports/agreement-line-production-summary', [
            'start' => $dateStart,
            'end' => $dateEnd
        ]);
        // Then
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $content['orders_pending'][0]['count']);
        $this->assertEquals(2, $content['orders_finished'][0]['count']);
    }

    public function testShouldGetResponseFromProductionFinishedDetails()
    {
        // Given
        $dateStart = '2021-09-01';
        $dateEnd = '2021-09-30';
        // When
        $this->client->xmlHttpRequest('GET', '/api/reports/production-finished-details', [
            'start' => $dateStart,
            'end' => $dateEnd
        ]);
        // Then
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
    public function testShouldGetResponseFromProductionPendingDetails()
    {
        // Given
        $dateStart = '2021-09-01';
        $dateEnd = '2021-09-30';
        // When
        $this->client->xmlHttpRequest('GET', '/api/reports/production-pending-details', [
            'start' => $dateStart,
            'end' => $dateEnd
        ]);
        // Then
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}