<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\System\CommandBus;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AgreementLineRmBadgeFiltersTest extends ApiTestCase
{
    private const CUSTOMER_NAME = 'BadgeFiltersCustomer';

    private EntityFactory $factory;
    private AgreementLineChainFactory $chainFactory;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
        $this->chainFactory = new AgreementLineChainFactory($this->factory);
        $this->customer = $this->factory->make(Customer::class, ['name' => self::CUSTOMER_NAME]);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldReturnOnlyLinesWithAnOverdueWaitingProduction(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        $client->disableReboot();

        $ids = [
            'overdue' => $this->makeLineWithProduction(
                TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                new \DateTime('-3 days')
            ),
            'notDueYet' => $this->makeLineWithProduction(
                TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                new \DateTime('+3 days')
            ),
            'started' => $this->makeLineWithProduction(
                TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
                new \DateTime('-3 days')
            ),
        ];
        $this->getManager()->clear();

        foreach ($ids as $id) {
            $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($id));
        }
        $this->getManager()->clear();

        // When
        $filtered = $this->searchIds($client, ['notStarted' => true]);

        // Then
        $this->assertContains($ids['overdue'], $filtered);
        $this->assertNotContains($ids['notDueYet'], $filtered);
        $this->assertNotContains($ids['started'], $filtered);

        // wyłączony filtr nie zawęża niczego
        $unfiltered = $this->searchIds($client, ['notStarted' => false]);
        foreach ($ids as $id) {
            $this->assertContains($id, $unfiltered);
        }
    }

    public function testShouldReturnOnlyLinesWithADelayedStart(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);
        $client->disableReboot();

        $delayed = $this->makeLineWithProduction(
            TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            new \DateTime('-3 days'),
            true
        );
        $onTime = $this->makeLineWithProduction(
            TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            new \DateTime('-3 days')
        );
        $this->getManager()->clear();

        foreach ([$delayed, $onTime] as $id) {
            $this->get(CommandBus::class)->dispatch(new UpdateAgreementLineRM($id));
        }
        $this->getManager()->clear();

        // When
        $filtered = $this->searchIds($client, ['startDelayed' => true]);

        // Then
        $this->assertContains($delayed, $filtered);
        $this->assertNotContains($onTime, $filtered);

        $unfiltered = $this->searchIds($client, ['startDelayed' => false]);
        $this->assertContains($delayed, $unfiltered);
        $this->assertContains($onTime, $unfiltered);
    }

    private function makeLineWithProduction(
        int $status,
        \DateTime $dateStart,
        bool $isStartDelayed = false
    ): int {
        $line = $this->chainFactory->make(['customer' => $this->customer]);

        $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => $status,
            'dateStart' => $dateStart,
            'dateEnd' => (clone $dateStart)->modify('+1 day'),
            'isStartDelayed' => $isStartDelayed,
        ]);
        $this->factory->flush();

        return $line->getId();
    }

    /**
     * @return int[]
     */
    private function searchIds(KernelBrowser $client, array $search): array
    {
        $client->request(
            'POST',
            '/agreement-line/rm/search',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['search' => $search + ['q' => self::CUSTOMER_NAME]]),
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $payload = json_decode($client->getResponse()->getContent(), true);

        return array_column($payload['data'], 'agreementLineId');
    }
}
