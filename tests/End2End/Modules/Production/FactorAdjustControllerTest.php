<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FactorAdjustControllerTest extends ApiTestCase
{
    private Production $production;


    protected function setUp(): void
    {
//        $this->getManager()->beginTransaction();

        $factory = new EntityFactory($this->getManager());
        $fixtureHelper = new AgreementLineFixtureHelpers(
            $factory,
            new AgreementLineChainFactory($factory)
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
        $this->production = $agreementLine->getProductions()->first();

        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    public function testShouldCreateFactorAdjust(): int
    {
        // Given
        $user = $this->createUser([], ['ROLE_ADMIN']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('POST', '/production/factor-adjust/create/' . $this->production->getId(), [
            'description' => 'Adjustment for testing',
            'factor' => 1.2
        ]);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        return json_decode($client->getResponse()->getContent(), true)['id'];
    }

    /** @depends testShouldCreateFactorAdjust */
    public function testShouldReadFactorAdjust(int $factorAdjustId): void
    {
        // Given
        $user = $this->createUser([], ['ROLE_ADMIN']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/production/factor-adjust/' . $factorAdjustId);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /** @depends testShouldCreateFactorAdjust */
    public function testShouldUpdateFactorAdjust(int $factorAdjustId): void
    {
        // Given
        $user = $this->createUser([], ['ROLE_ADMIN']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('PUT', '/production/factor-adjust/' . $factorAdjustId);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /** @depends testShouldCreateFactorAdjust */
    public function testShouldDeleteFactorAdjust(int $factorAdjustId): void
    {
        // Given
        $user = $this->createUser([], ['ROLE_ADMIN']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('DELETE', '/production/factor-adjust/' . $factorAdjustId);

        // Then
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

}