<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\System\CommandBus;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionStartRefreshesReadModelTest extends ApiTestCase
{
    private EntityFactory $factory;
    private AgreementLineChainFactory $chainFactory;
    private AgreementLineRMRepository $rmRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
        $this->chainFactory = new AgreementLineChainFactory($this->factory);
        $this->rmRepository = $this->get(AgreementLineRMRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testStartingProductionMakesGhostLineVisibleOnProductionList(): void
    {
        // Given — a waiting line whose productions are all ghosts (status AWAITS),
        // mirroring an order that has only pending/ghost production tasks.
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        // Keep the same kernel (and DB transaction / session) across the multiple
        // requests this test issues.
        $client->disableReboot();

        $line = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $lineId = $line->getId();
        $orderNumber = (string) $line->getAgreement()->getOrderNumber();

        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            $this->factory->make(Production::class, [
                'agreementLine' => $line,
                'departmentSlug' => $dept->value,
                'isGhost' => true,
            ]);
        }
        $this->factory->flush();
        // Clear so the read-model build reloads the line (and its productions) from
        // the database instead of the in-memory line whose collection is still empty.
        $this->getManager()->clear();

        // Build the read model so it reflects the ghost productions.
        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($lineId));
        $this->getManager()->clear();

        // Sanity — while all productions are ghosts the line is hidden from the list.
        $this->assertSame(
            0,
            $this->searchProductionListCount($client, $orderNumber),
            'A line with only ghost productions must not appear on the production list',
        );

        // When — production is started (ghosts are un-ghosted).
        $client->request('POST', '/production/start/' . $lineId);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        // Then — the read model no longer reports any ghost production...
        /** @var AgreementLineRM $model */
        $model = $this->rmRepository->find($lineId);
        $this->assertNotNull($model, 'Read model must exist after starting production');
        foreach ($model->getProductions() as $production) {
            $this->assertFalse(
                $production->isGhost(),
                'Read model production must not stay flagged as ghost after starting production',
            );
        }

        // ...and the line is now visible on the production list.
        $this->assertSame(
            1,
            $this->searchProductionListCount($client, $orderNumber),
            'A started line must appear on the production list',
        );
    }

    private function searchProductionListCount(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $q): int
    {
        $client->request(
            'POST',
            '/agreement-line/rm/search',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['search' => ['q' => $q, 'hideArchive' => true]]),
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $payload = json_decode($client->getResponse()->getContent(), true);

        return (int) ($payload['meta']['totalCount'] ?? -1);
    }
}
