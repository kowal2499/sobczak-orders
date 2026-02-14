<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\Response;

class FactorControllerTest extends ApiTestCase
{
    private EntityFactory $entityFactory;
    private AgreementLine $agreementLine;

    protected function setUp(): void
    {
        $this->getManager()->beginTransaction();

        $this->entityFactory = new EntityFactory($this->getManager());
        $fixtureHelper = new AgreementLineFixtureHelpers(
            $this->entityFactory,
            new AgreementLineChainFactory($this->entityFactory)
        );

        $this->agreementLine = $fixtureHelper->makeAgreementLineWithProductionTasks([
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

    }

    public function testShouldStoreFactorFormData(): void
    {
        // Given
        $user = $this->createUser([], [], ['production.factor_adjustment']);
        $client = $this->login($user);
        // When
        $client->xmlHttpRequest('POST',
            '/production/factor/' . $this->agreementLine->getId(),
            [
                'factor' => 1.2,
                'factorAdjustmentBonus' => [
                    [
                        'id' => null,
                        'description' => 'Some desc 01',
                        'value' => 0.5,
                        'departmentSlug' => 'dpt01'
                    ],
                    [
                        'id' => null,
                        'description' => 'Some desc 02',
                        'value' => 0.1,
                        'departmentSlug' => 'dpt03'
                    ]
                ],
                'factorAdjustmentRatio' => [
                    [
                        'departmentSlug' => 'dpt01',
                        'value' => 0.5
                    ],
                    [
                        'departmentSlug' => 'dpt02',
                        'value' => 0.2
                    ]
                ]
            ]
        );
        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
//        $production = $this->getProduction();
//        $faRepository = $this->getManager()->getRepository(FactorAdjustment::class);
//        $factorAdjusts = $faRepository->findBy(['description' => 'Adjustment for testing', 'factor' => 1.2, 'production' => $production]);
    }

    public function testShouldReadFactorFormData(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/production/factor/' . $this->agreementLine->getId());

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
//        $data = json_decode($client->getResponse()->getContent(), true);
//        $this->assertEquals('Adjustment for testing', $data['description']);
//        $this->assertEquals(1.2, $data['factor']);
//        $this->assertEquals($this->productionId, $data['productionId']);
    }

    public function testShouldUpdateFactor(): void
    {
        $this->markTestSkipped();
        // Given
//        $user = $this->createUser([], [], ['production.factor_adjustment_bonus:update']);
//        $client = $this->login($user);
//        $factorAdjust = $this->createFactorAdjust('Adjustment for testing', 1.2);

        // When
//        $client->xmlHttpRequest('PUT', '/production/factor-adjustment/' . $factorAdjust->getId(), [
//            'description' => 'Updated description',
//            'factor' => 1.5
//        ]);

        // Then
//        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
//        $updatedFactorAdjust = $this->getManager()->find(FactorAdjustment::class, $factorAdjust->getId());
//        $this->assertEquals('Updated description', $updatedFactorAdjust->getDescription());
//        $this->assertEquals(1.5, $updatedFactorAdjust->getFactor());
    }

    public function testShouldDeleteFactor(): void
    {
        $this->markTestSkipped();
        // Given
//        $user = $this->createUser([], [], ['production.factor_adjustment_bonus:update']);
//        $client = $this->login($user);
//        $factorAdjust = $this->createFactorAdjust('Adjustment for testing', 1.2);
//        $factorAdjustId = $factorAdjust->getId();

        // When
//        $client->xmlHttpRequest('DELETE', '/production/factor-adjustment/' . $factorAdjustId);

        // Then
//        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
//        $this->assertNull($this->getManager()->find(FactorAdjustment::class, $factorAdjustId));
    }

//    private function getProduction(): Production
//    {
//        return $this->getManager()->find(Production::class, $this->productionId);
//    }

//    private function createFactorAdjust(string $description, float $factor): FactorAdjustment
//    {
//        $factorAdjust = $this->entityFactory->make(FactorAdjustment::class, [
//            'production' => $this->getProduction(),
//            'description' => $description,
//            'factor' => $factor
//        ]);
//
//        $this->getManager()->flush();
//        return $factorAdjust;
//    }

}