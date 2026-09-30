<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\System\CommandBus;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AgreementLineRmOrdersControllerTest extends ApiTestCase
{
    private EntityFactory $factory;
    private AgreementLineChainFactory $chainFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
        $this->chainFactory = new AgreementLineChainFactory($this->factory);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testOrdersListShowsLineWithOnlyGhostProductions(): void
    {
        // Given - a waiting line whose productions are all ghosts. The production
        // list (/rm/search, hasProduction=true) hides such a line; the orders
        // list must still show it.
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
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
        $this->getManager()->clear();

        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($lineId));
        $this->getManager()->clear();

        // Then - hidden from the production list...
        $this->assertSame(0, $this->totalCount($client, '/agreement-line/rm/search', $orderNumber));

        // ...but present on the orders list.
        $orders = $this->search($client, '/agreement-line/rm/orders', ['q' => $orderNumber]);
        $this->assertSame(1, (int) ($orders['meta']['totalCount'] ?? -1));

        // And the row keeps the legacy payload shape the orders list reads.
        $row = $orders['data'][0];
        $this->assertSame($lineId, $row['id']);
        $this->assertSame(AgreementLine::STATUS_WAITING, $row['status']);
        $this->assertSame($orderNumber, (string) $row['Agreement']['orderNumber']);
        $this->assertArrayHasKey('createDate', $row['Agreement']);
        $this->assertArrayHasKey('Customer', $row['Agreement']);
        $this->assertArrayHasKey('name', $row['Agreement']['Customer']);
        $this->assertArrayHasKey('user', $row['Agreement']);
        $this->assertArrayHasKey('attachments', $row['Agreement']);
        $this->assertArrayHasKey('name', $row['Product']);
        $this->assertIsArray($row['tags']);
        $this->assertNotEmpty($row['productions']);
        $this->assertArrayHasKey('isGhost', $row['productions'][0]);
        $this->assertTrue($row['productions'][0]['isGhost']);
    }

    public function testOrdersListFiltersByStatus(): void
    {
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        $client->disableReboot();

        $waiting = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $completed = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $this->factory->flush();
        $waitingId = $waiting->getId();
        $completedId = $completed->getId();
        $this->getManager()->clear();

        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($waitingId));
        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($completedId));
        $this->getManager()->clear();

        $result = $this->search($client, '/agreement-line/rm/orders', [
            'status' => AgreementLine::STATUS_WAITING,
        ]);

        $ids = array_column($result['data'], 'id');
        $this->assertContains($waitingId, $ids);
        $this->assertNotContains($completedId, $ids);
    }

    public function testOrdersListFiltersOverdue(): void
    {
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        $client->disableReboot();

        $lines = [
            'overdue' => ['status' => AgreementLine::STATUS_MANUFACTURING, 'confirmedDate' => new \DateTime('-3 days')],
            'dueToday' => ['status' => AgreementLine::STATUS_MANUFACTURING, 'confirmedDate' => new \DateTime('today')],
            'future' => ['status' => AgreementLine::STATUS_WAITING, 'confirmedDate' => new \DateTime('+3 days')],
            'delivered' => ['status' => AgreementLine::STATUS_WAREHOUSE, 'confirmedDate' => new \DateTime('-3 days')],
        ];
        $orderNumbers = [];
        foreach ($lines as $name => $attributes) {
            $line = $this->chainFactory->make([], $attributes);
            $this->factory->flush();
            $orderNumbers[$name] = (string) $line->getAgreement()->getOrderNumber();
            $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($line->getId()));
        }
        $this->getManager()->clear();

        $matches = [];
        foreach ($orderNumbers as $name => $orderNumber) {
            $result = $this->search($client, '/agreement-line/rm/orders', ['q' => $orderNumber, 'overdue' => true]);
            $matches[$name] = (int) $result['meta']['totalCount'] > 0;
        }

        $this->assertSame(
            ['overdue' => true, 'dueToday' => false, 'future' => false, 'delivered' => false],
            $matches
        );
    }

    public function testOrdersListFiltersByCustomerAndAuthor(): void
    {
        $viewer = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($viewer);
        $client->disableReboot();

        $anna = $this->createUser()->setFirstName('Anna')->setLastName('Autorka');
        $piotr = $this->createUser();

        $annaFirst = $this->chainFactory->make(['user' => $anna]);
        $piotrs = $this->chainFactory->make(['user' => $piotr]);
        $annaSecond = $this->chainFactory->make(['user' => $anna]);
        $ids = [$annaFirst->getId(), $piotrs->getId(), $annaSecond->getId()];
        $customerOf = fn (AgreementLine $line) => $line->getAgreement()->getCustomer()->getId();
        $customers = [$customerOf($annaFirst), $customerOf($piotrs)];
        $authors = [$anna->getId(), $piotr->getId()];
        $this->getManager()->clear();

        foreach ($ids as $id) {
            $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($id));
        }
        $this->getManager()->clear();

        $byCustomers = $this->search($client, '/agreement-line/rm/orders', ['customers' => $customers]);
        $this->assertEqualsCanonicalizing([$ids[0], $ids[1]], array_column($byCustomers['data'], 'id'));

        $byAuthor = $this->search($client, '/agreement-line/rm/orders', ['authors' => [$anna->getId()]]);
        $this->assertEqualsCanonicalizing([$ids[0], $ids[2]], array_column($byAuthor['data'], 'id'));

        $combined = $this->search($client, '/agreement-line/rm/orders', [
            'customers' => $customers,
            'authors' => [$anna->getId()],
        ]);
        $this->assertSame([$ids[0]], array_column($combined['data'], 'id'));

        $client->request('GET', '/agreement-line/rm/orders/filter-options');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $options = json_decode($client->getResponse()->getContent(), true);
        $this->assertEmpty(array_diff($customers, array_column($options['customers'], 'id')));
        $this->assertContains(['id' => $anna->getId(), 'name' => 'Anna Autorka'], $options['authors']);
        $this->assertEmpty(array_diff($authors, array_column($options['authors'], 'id')));
    }

    public function testFilterOptionsAreLimitedToOwnCustomers(): void
    {
        $author = $this->createUser();
        $own = $this->chainFactory->make();
        $foreign = $this->chainFactory->make(['user' => $author]);
        $ownCustomer = $own->getAgreement()->getCustomer();
        $foreignCustomerId = $foreign->getAgreement()->getCustomer()->getId();

        $viewer = $this->createUser([], [], [], ['ROLE_CUSTOMER', 'ROLE_PRODUCTION']);
        $viewer->addCustomer($ownCustomer);
        $this->getManager()->flush();
        $ownId = $own->getId();
        $foreignId = $foreign->getId();
        $ownCustomerId = $ownCustomer->getId();
        $authorId = $author->getId();
        $this->getManager()->clear();

        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($ownId));
        $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($foreignId));
        $this->getManager()->clear();

        $client = $this->login($viewer);
        $client->request('GET', '/agreement-line/rm/orders/filter-options');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $options = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame([$ownCustomerId], array_column($options['customers'], 'id'));
        $this->assertNotContains($foreignCustomerId, array_column($options['customers'], 'id'));
        $this->assertNotContains($authorId, array_column($options['authors'], 'id'));
    }

    private function search(KernelBrowser $client, string $url, array $search): array
    {
        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['search' => $search]),
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function totalCount(KernelBrowser $client, string $url, string $q): int
    {
        $payload = $this->search($client, $url, ['q' => $q, 'hideArchive' => true]);

        return (int) ($payload['meta']['totalCount'] ?? -1);
    }
}
