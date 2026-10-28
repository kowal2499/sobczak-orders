<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\User;
use App\Module\Reports\Production\Metric\AttentionListsMetricStrategy;

class AttentionListsTest extends BaseProductionReportsTestCase
{
    private const URL = '/reports/production/attention-lists';

    public function testReturnsFourEmptyListsWhenNothingNeedsAttention(): void
    {
        $client = $this->login($this->userWithAllLists());

        $client->xmlHttpRequest('GET', self::URL);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([
            'overdueOrders' => [],
            'unplannedOrders' => [],
            'notStartedProductions' => [],
            'overdueProductions' => [],
        ], $this->lists($client));
    }

    public function testReturnsOnlyListsCoveredByDashboardGrantOptions(): void
    {
        $client = $this->login($this->createUser(
            [],
            [],
            [AttentionListsMetricStrategy::LIST_GRANTS['unplannedOrders']],
            ['ROLE_PRODUCTION'],
        ));
        $this->makeAgreementLine(confirmedDate: new \DateTime('-3 days'));

        $client->xmlHttpRequest('GET', self::URL);

        $this->assertSame(['unplannedOrders'], array_keys($this->lists($client)));
    }

    public function testReturnsNoListsWithoutAnyGrantOption(): void
    {
        $client = $this->login($this->createUser([], [], [], ['ROLE_PRODUCTION']));

        $client->xmlHttpRequest('GET', self::URL);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], $this->lists($client));
    }

    public function testOverdueOrdersSkipFinishedOrdersAndSortByDaysDescending(): void
    {
        $client = $this->login($this->userWithAllLists());

        $slightly = $this->makeAgreementLine(confirmedDate: new \DateTime('-3 days'));
        $badly = $this->makeAgreementLine(confirmedDate: new \DateTime('-10 days'));
        $this->makeAgreementLine(confirmedDate: new \DateTime('-20 days'), status: AgreementLine::STATUS_ARCHIVED);
        $this->makeAgreementLine(confirmedDate: new \DateTime('-20 days'), status: AgreementLine::STATUS_WAREHOUSE);
        $this->makeAgreementLine(confirmedDate: new \DateTime('+5 days'));

        $client->xmlHttpRequest('GET', self::URL);

        $overdue = $this->lists($client)['overdueOrders'];
        $this->assertSame([$badly->getId(), $slightly->getId()], array_column($overdue, 'agreementLineId'));
        $this->assertSame([10, 3], array_column($overdue, 'days'));
    }

    public function testUnplannedOrdersAreThoseWithOnlyForecastProduction(): void
    {
        $client = $this->login($this->userWithAllLists());

        $unplanned = $this->makeAgreementLine(
            status: AgreementLine::STATUS_WAITING,
            confirmedDate: new \DateTime('+30 days'),
            productions: [['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING, 'isGhost' => true]],
        );
        $this->makeAgreementLine(
            confirmedDate: new \DateTime('+30 days'),
            productions: [$this->production('dpt01', 0, '+1 day', '+2 days')],
        );

        $client->xmlHttpRequest('GET', self::URL);

        $unplannedIds = array_column($this->lists($client)['unplannedOrders'], 'agreementLineId');
        $this->assertSame([$unplanned->getId()], $unplannedIds);
    }

    public function testProductionListsPickNotStartedAndOverdueDepartments(): void
    {
        $client = $this->login($this->userWithAllLists(
            ['production.show.gluing', 'production.show.cnc', 'production.show.grinding']
        ));

        $line = $this->makeAgreementLine(
            confirmedDate: new \DateTime('+30 days'),
            productions: [
                $this->production('dpt01', TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, '-4 days', '+2 days'),
                $this->production('dpt02', TaskTypes::TYPE_DEFAULT_STATUS_PENDING, '-9 days', '-6 days'),
                $this->production('dpt03', TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, '-9 days', '-6 days'),
            ],
        );

        $client->xmlHttpRequest('GET', self::URL);

        $lists = $this->lists($client);
        $this->assertSame($line->getId(), $lists['notStartedProductions'][0]['agreementLineId']);
        $this->assertSame([['dpt01', 4]], $this->departmentDays($lists['notStartedProductions'][0]));
        $this->assertSame([['dpt02', 6]], $this->departmentDays($lists['overdueProductions'][0]));
        $this->assertSame(6, $lists['overdueProductions'][0]['days']);
    }

    public function testHidesDepartmentsWithoutVisibilityGrant(): void
    {
        $client = $this->login($this->userWithAllLists(['production.show.gluing']));

        $this->makeAgreementLine(
            confirmedDate: new \DateTime('+30 days'),
            productions: [
                $this->production('dpt02', TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, '-4 days', '-1 day'),
            ],
        );

        $client->xmlHttpRequest('GET', self::URL);

        $lists = $this->lists($client);
        $this->assertSame([], $lists['notStartedProductions']);
        $this->assertSame([], $lists['overdueProductions']);
    }

    public function testFiltersByOwnedCustomersForRoleCustomer(): void
    {
        $user = $this->userWithAllLists([], ['ROLE_CUSTOMER', 'ROLE_PRODUCTION']);
        $owned = $this->factory->make(Customer::class);
        $other = $this->factory->make(Customer::class);
        $this->factory->flush();
        $user->addCustomer($owned);
        $this->factory->flush();
        $client = $this->login($user);

        $mine = $this->makeAgreementLine(customer: $owned, confirmedDate: new \DateTime('-2 days'));
        $this->makeAgreementLine(customer: $other, confirmedDate: new \DateTime('-2 days'));

        $client->xmlHttpRequest('GET', self::URL);

        $this->assertSame([$mine->getId()], array_column($this->lists($client)['overdueOrders'], 'agreementLineId'));
    }

    public function testDeniesAccessWithoutRoleProduction(): void
    {
        $client = $this->login($this->userWithAllLists([], ['ROLE_CUSTOMER']));

        $client->xmlHttpRequest('GET', self::URL);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    private function userWithAllLists(array $extraGrants = [], array $roles = ['ROLE_PRODUCTION']): User
    {
        return $this->createUser(
            [],
            [],
            [...array_values(AttentionListsMetricStrategy::LIST_GRANTS), ...$extraGrants],
            $roles,
        );
    }

    private function lists($client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }

    private function production(string $slug, int $status, string $dateStart, string $dateEnd): array
    {
        return [
            'slug' => $slug,
            'status' => (string) $status,
            'isCompleted' => $status === TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'dateStart' => new \DateTime($dateStart),
            'dateEnd' => new \DateTime($dateEnd),
        ];
    }

    private function departmentDays(array $line): array
    {
        return array_map(fn (array $p) => [$p['departmentSlug'], $p['daysLate']], $line['productions']);
    }
}
