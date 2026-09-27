<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\User;
use App\Tests\End2End\Modules\Reports\Production\BaseProductionReportsTestCase;

/**
 * Użytkownik z ROLE_CUSTOMER nie może czytać ani zmieniać rekordów klientów, których
 * nie ma przypisanych - niezależnie od grantów, które pozwalają mu na dany endpoint.
 */
class CustomerOwnershipTest extends BaseProductionReportsTestCase
{
    private const GRANTS = ['activity-log.read', 'production.panel', 'order.manage', 'production.factor_adjustment'];

    private Customer $ownCustomer;
    private Customer $foreignCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ownCustomer = $this->factory->make(Customer::class);
        $this->foreignCustomer = $this->factory->make(Customer::class);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function readEndpoints(): iterable
    {
        yield 'read model line' => ['GET', '/agreement-line/rm/{line}'];
        yield 'legacy line' => ['GET', '/api/agreement-line/fetch-single/{line}'];
        yield 'order panel page' => ['GET', '/agreement/line/{line}'];
        yield 'order edit data' => ['POST', '/orders/fetch_single/{agreement}'];
        yield 'order edit page' => ['GET', '/orders/edit/{agreement}'];
        yield 'factors' => ['GET', '/production/factor/{line}'];
        yield 'tasks of line' => ['GET', '/tasks/find?agreementLineId={line}'];
        yield 'line activity log' => ['GET', '/agreement-line/{line}/activity-log'];
        yield 'order activity log' => ['GET', '/agreement/{agreement}/activity-log'];
    }

    /**
     * @dataProvider readEndpoints
     */
    public function testShouldDenyReadingForeignCustomerRecord(string $method, string $url): void
    {
        // Given
        $line = $this->makeAgreementLine(customer: $this->foreignCustomer);
        $client = $this->login($this->customerUser());

        // When
        $client->xmlHttpRequest($method, $this->resolve($url, $line));

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider readEndpoints
     */
    public function testShouldAllowReadingOwnCustomerRecord(string $method, string $url): void
    {
        // Given
        $line = $this->makeAgreementLine(customer: $this->ownCustomer);
        $client = $this->login($this->customerUser());

        // When
        $client->xmlHttpRequest($method, $this->resolve($url, $line));

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider readEndpoints
     */
    public function testShouldAllowReadingAnyRecordWithoutRoleCustomer(string $method, string $url): void
    {
        // Given
        $line = $this->makeAgreementLine(customer: $this->foreignCustomer);
        $client = $this->login($this->createUser([], [], self::GRANTS, ['ROLE_PRODUCTION']));

        // When
        $client->xmlHttpRequest($method, $this->resolve($url, $line));

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testShouldNotListForeignTasksByType(): void
    {
        // Given
        $foreignLine = $this->makeAgreementLine(customer: $this->foreignCustomer);
        $client = $this->login($this->customerUser());
        $client->xmlHttpRequest('POST', '/tasks', [], [], [], json_encode([
            'agreementLineId' => $foreignLine->getId(),
            'status' => 10,
            'type' => 'task_custom',
        ]));

        // When
        $client->xmlHttpRequest('GET', '/tasks/find?type=task_custom');

        // Then - zadanie nie powstało, a lista po typie nie zwraca cudzych
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    /**
     * @return iterable<string, array{string, string, array<string, mixed>}>
     */
    public static function writeEndpoints(): iterable
    {
        yield 'delete order' => ['POST', '/orders/delete/{agreement}', []];
        yield 'change line status' => ['POST', '/agreement_line/archive/{line}/' . AgreementLine::STATUS_ARCHIVED, []];
        yield 'delete line' => ['POST', '/agreement_line/delete/{line}', []];
        yield 'start production' => ['POST', '/production/start/{line}', []];
        yield 'production status' => ['POST', '/production/update_status', ['productionId' => '{production}', 'newStatus' => 1]];
        yield 'production dates' => ['PUT', '/production/{production}/dates', ['dateEnd' => '2026-05-20']];
        yield 'ghost production dates' => ['PUT', '/production/ghost/{production}/dates', ['dateEnd' => '2026-05-20']];
        yield 'add factor' => ['POST', '/production/factor/{line}', []];
        yield 'create task' => ['POST', '/tasks', ['agreementLineId' => '{line}', 'status' => 10, 'type' => 'task_custom']];
    }

    /**
     * @dataProvider writeEndpoints
     * @param array<string, mixed> $body
     */
    public function testShouldDenyChangingForeignCustomerRecord(string $method, string $url, array $body): void
    {
        // Given
        $line = $this->makeAgreementLine(
            customer: $this->foreignCustomer,
            productions: [['slug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING]],
        );
        $client = $this->login($this->customerUser());
        $body = array_map(
            fn ($value) => is_string($value) && str_starts_with($value, '{') ? (int) $this->resolve($value, $line) : $value,
            $body
        );

        // When - update_status czyta formularz, pozostałe JSON
        if ($url === '/production/update_status') {
            $client->xmlHttpRequest($method, $url, $body);
        } else {
            $client->xmlHttpRequest($method, $this->resolve($url, $line), [], [], [], json_encode($body));
        }

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldDenyCreatingOrderForForeignCustomer(): void
    {
        // Given
        $client = $this->login($this->customerUser());

        // When
        $client->xmlHttpRequest('POST', '/orders/save', [
            'customerId' => $this->foreignCustomer->getId(),
            'orderNumber' => 'X-1',
            'products' => json_encode([['productId' => 1, 'factor' => 1]]),
        ]);

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldDenyMovingOwnOrderToForeignCustomer(): void
    {
        // Given
        $line = $this->makeAgreementLine(customer: $this->ownCustomer);
        $client = $this->login($this->customerUser());

        // When
        $client->xmlHttpRequest('POST', '/orders/patch/' . $line->getAgreement()->getId(), [
            'customerId' => $this->foreignCustomer->getId(),
            'orderNumber' => 'X-1',
            'products' => json_encode([['productId' => 1, 'factor' => 1]]),
        ]);

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldRequireOrderManageToDeleteOrder(): void
    {
        // Given
        $line = $this->makeAgreementLine(customer: $this->foreignCustomer);
        $client = $this->login($this->createUser(legacyRoles: ['ROLE_PRODUCTION']));

        // When
        $client->xmlHttpRequest('POST', '/orders/delete/' . $line->getAgreement()->getId());

        // Then
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    private function customerUser(): User
    {
        $user = $this->createUser([], [], self::GRANTS, ['ROLE_CUSTOMER', 'ROLE_PRODUCTION']);
        $user->addCustomer($this->ownCustomer);
        $this->getManager()->flush();

        return $user;
    }

    private function resolve(string $template, AgreementLine $line): string
    {
        $production = $line->getProductions()->first();

        return strtr($template, [
            '{line}' => (string) $line->getId(),
            '{agreement}' => (string) $line->getAgreement()->getId(),
            '{production}' => $production ? (string) $production->getId() : '0',
        ]);
    }
}
