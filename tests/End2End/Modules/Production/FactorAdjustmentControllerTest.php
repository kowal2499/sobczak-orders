<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\Production\Entity\FactorAdjustment;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\Response;

class FactorAdjustmentControllerTest extends ApiTestCase
{
    private int $productionId;
    private EntityFactory $entityFactory;

    protected function setUp(): void
    {
        $this->getManager()->beginTransaction();

        $this->entityFactory = new EntityFactory($this->getManager());
        $fixtureHelper = new AgreementLineFixtureHelpers(
            $this->entityFactory,
            new AgreementLineChainFactory($this->entityFactory)
        );

        $agreementLine = $fixtureHelper->makeAgreementLineWithProductionTasks([
            'status' => AgreementLine::STATUS_WAITING,
            'productionStartDate' => new \DateTime('2025-02-15 12:00:00'),
            'confirmedDate' => new \DateTime('2024-12-13 12:01:01'),
            'description' => 'Lorem ipsum',
            'factor' => 0.7
        ], [
            'dpt01' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt02' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt03' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt04' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt05' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
        ]);
        $this->productionId = $agreementLine->getProductions()->first()->getId();

        $this->getManager()->flush();
    }

    public function testShouldCreateFactorAdjust(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.factor_adjustment:create']);
        $client = $this->login($user);
        // When
        $client->xmlHttpRequest('POST', '/production/factor-adjustment/create/' . $this->productionId, [
            'description' => 'Adjustment for testing',
            'factor' => 1.2
        ]);
        // Then
        $production = $this->getProduction();
        $faRepository = $this->getManager()->getRepository(FactorAdjustment::class);
        $factorAdjusts = $faRepository->findBy(['description' => 'Adjustment for testing', 'factor' => 1.2, 'production' => $production]);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $factorAdjusts);
        $this->assertEquals(1.2, $factorAdjusts[0]->getFactor());
        $this->assertEquals('Adjustment for testing', $factorAdjusts[0]->getDescription());
    }

    public function testShouldReadFactorAdjust(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.factor_adjustment:read']);
        $client = $this->login($user);
        $factorAdjust = $this->createFactorAdjust('Adjustment for testing', 1.2);

        // When
        $client->xmlHttpRequest('GET', '/production/factor-adjustment/' . $factorAdjust->getId());

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Adjustment for testing', $data['description']);
        $this->assertEquals(1.2, $data['factor']);
        $this->assertEquals($this->productionId, $data['productionId']);
    }

    public function testShouldUpdateFactorAdjust(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.factor_adjustment:update']);
        $client = $this->login($user);
        $factorAdjust = $this->createFactorAdjust('Adjustment for testing', 1.2);

        // When
        $client->xmlHttpRequest('PUT', '/production/factor-adjustment/' . $factorAdjust->getId(), [
            'description' => 'Updated description',
            'factor' => 1.5
        ]);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $updatedFactorAdjust = $this->getManager()->find(FactorAdjustment::class, $factorAdjust->getId());
        $this->assertEquals('Updated description', $updatedFactorAdjust->getDescription());
        $this->assertEquals(1.5, $updatedFactorAdjust->getFactor());
    }

    public function testShouldDeleteFactorAdjust(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.factor_adjustment:delete']);
        $client = $this->login($user);
        $factorAdjust = $this->createFactorAdjust('Adjustment for testing', 1.2);
        $factorAdjustId = $factorAdjust->getId();

        // When
        $client->xmlHttpRequest('DELETE', '/production/factor-adjustment/' . $factorAdjustId);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertNull($this->getManager()->find(FactorAdjustment::class, $factorAdjustId));
    }

    private function getProduction(): Production
    {
        return $this->getManager()->find(Production::class, $this->productionId);
    }

    private function createFactorAdjust(string $description, float $factor): FactorAdjustment
    {
        $factorAdjust = $this->entityFactory->make(FactorAdjustment::class, [
            'production' => $this->getProduction(),
            'description' => $description,
            'factor' => $factor
        ]);

        $this->getManager()->flush();
        return $factorAdjust;
    }

}