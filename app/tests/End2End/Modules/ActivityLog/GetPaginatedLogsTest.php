<?php

namespace App\Tests\End2End\Modules\ActivityLog;

use App\Entity\User;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\QueryHandler\GetPaginatedLogsQueryHandler;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\Test\ApiTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class GetPaginatedLogsTest extends ApiTestCase
{
    private ActivityLogRepository $activityLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldFilterByTypeAndSortDescending(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog('agreement.created', 'a1', $user, ['agreementId' => '1'], new \DateTime('2026-05-10 09:00:00'));
        $this->seedLog('agreement.created', 'a2', $user, ['agreementId' => '2'], new \DateTime('2026-05-12 09:00:00'));
        $this->seedLog('production.started', 'p1', $user, ['productionId' => '5'], new \DateTime('2026-05-13 09:00:00'));
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('GET', '/log/agreement.created', [], [], ['CONTENT_TYPE' => 'application/json']);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $payload['total']);
        $this->assertCount(2, $payload['items']);
        $this->assertEquals('a2', $payload['items'][0]['content'], 'newest log first');
        $this->assertEquals('a1', $payload['items'][1]['content']);
        foreach ($payload['items'] as $item) {
            $this->assertEquals('agreement.created', $item['type']);
        }
    }

    public function testShouldFilterByFieldValue(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog('agreement.created', 'a1', $user, ['customerId' => '10']);
        $this->seedLog('agreement.created', 'a2', $user, ['customerId' => '20']);
        $this->seedLog('agreement.created', 'a3', $user, ['customerId' => '20']);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request(
            'GET',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fields' => [['name' => 'customerId', 'value' => '20']],
            ]),
        );

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $payload['total']);

        $contents = array_column($payload['items'], 'content');
        sort($contents);
        $this->assertSame(['a2', 'a3'], $contents);
    }

    public function testShouldFilterByFieldValuesArray(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog('agreement.created', 'a1', $user, ['customerId' => '10']);
        $this->seedLog('agreement.created', 'a2', $user, ['customerId' => '20']);
        $this->seedLog('agreement.created', 'a3', $user, ['customerId' => '30']);
        $this->getManager()->flush();
        $this->getManager()->clear();

        $client->request(
            'GET',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fields' => [['name' => 'customerId', 'values' => ['10', '30']]],
            ]),
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $payload['total']);

        $contents = array_column($payload['items'], 'content');
        sort($contents);
        $this->assertSame(['a1', 'a3'], $contents);
    }

    public function testFilterByLimitsReturnedFieldsToAllowedSet(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog(
            'agreement.created',
            'a1',
            $user,
            ['customerId' => '10', 'agreementId' => '1', 'note' => 'hidden'],
        );
        $this->getManager()->flush();
        $this->getManager()->clear();

        $client->request(
            'GET',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fields' => [['name' => 'customerId', 'value' => '10']],
                'filterBy' => 'agreementId',
            ]),
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $payload['total']);

        $names = array_column($payload['items'][0]['fields'], 'name');
        sort($names);
        $this->assertSame(['agreementId', 'customerId'], $names, 'only fields named in fields[].name and filterBy should be returned');
    }

    public function testShouldPaginate(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        for ($i = 1; $i <= 5; $i++) {
            $this->seedLog(
                'agreement.created',
                'msg-' . $i,
                $user,
                ['idx' => (string) $i],
                new \DateTime(sprintf('2026-05-%02d 09:00:00', $i)),
            );
        }
        $this->getManager()->flush();
        $this->getManager()->clear();

        $client->request(
            'GET',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['page' => 2, 'pageSize' => 2]),
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(5, $payload['total']);
        $this->assertEquals(2, $payload['page']);
        $this->assertEquals(2, $payload['pageSize']);
        $this->assertCount(2, $payload['items']);
        $this->assertEquals('msg-3', $payload['items'][0]['content']);
        $this->assertEquals('msg-2', $payload['items'][1]['content']);
    }

    public function testShouldReturnTranslatedContentForKnownKey(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $log = new ActivityLog(
            'agreement.created',
            'activity_log.agreement.created',
            $user,
            LogLevel::INFO,
            LogPriority::normal,
        );
        $log->addLogField('id', '7');
        $this->activityLogRepository->save($log, true);
        $this->getManager()->clear();

        // When
        $client->request('GET', '/log/agreement.created', [], [], ['CONTENT_TYPE' => 'application/json']);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $payload['items']);
        $this->assertSame('Utworzenie zamówienia', $payload['items'][0]['content']);
    }

    public function testShouldExposeContentParamsInSerializedResponse(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $log = new ActivityLog(
            'agreement.status_changed',
            'activity_log.agreement.status_changed',
            $user,
            LogLevel::INFO,
            LogPriority::normal,
            ['old' => 'DRAFT', 'new' => 'WAITING'],
        );
        $log->addLogField('id', '99');
        $this->activityLogRepository->save($log, true);
        $this->getManager()->clear();

        // When
        $client->request('GET', '/log/agreement.status_changed', [], [], ['CONTENT_TYPE' => 'application/json']);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $payload['items']);
        $this->assertSame(
            ['old' => 'DRAFT', 'new' => 'WAITING'],
            $payload['items'][0]['contentParams'],
        );
        $this->assertSame('activity_log.agreement.status_changed', $payload['items'][0]['content']);
    }

    public function testShouldReturn403WithoutGrant(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request('GET', '/log/agreement.created');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, string> $fields
     */
    public function testShouldLocalizeContentParamValuesOnRead(): void
    {
        // Given — a status-change log whose content params hold canonical Polish enum names
        $user = $this->createUser([], [], ['activity-log.read']);

        $log = new ActivityLog(
            'agreement_line.production_status_changed',
            'activity_log.agreement_line.production_status_changed',
            $user,
            LogLevel::INFO,
            LogPriority::normal,
            ['departmentName' => 'Klejenie', 'oldStatusName' => 'Oczekuje', 'newStatusName' => 'W trakcie'],
        );
        $log->addLogField('agreementId', '4321');
        $this->activityLogRepository->save($log, true);
        $this->getManager()->clear();

        /** @var TranslatorInterface $translator */
        $translator = $this->get(TranslatorInterface::class);
        $translator->setLocale('en');

        /** @var GetPaginatedLogsQueryHandler $handler */
        $handler = $this->get(GetPaginatedLogsQueryHandler::class);

        // When — read in the English locale
        $result = $handler(new GetPaginatedLogsQuery(
            'agreement_line.production_status_changed',
            new PaginatedLogFilter(1, 50),
        ));

        // Then — enum-derived values are localized on the backend
        $this->assertCount(1, $result->items);
        $params = $result->items[0]->contentParams;
        $this->assertSame('Gluing', $params['departmentName']);
        $this->assertSame('Awaiting', $params['oldStatusName']);
        $this->assertSame('In progress', $params['newStatusName']);
    }

    private function seedLog(
        string $type,
        string $content,
        User $user,
        array $fields,
        ?\DateTimeInterface $createdAt = null,
    ): ActivityLog {
        $log = new ActivityLog($type, $content, $user, LogLevel::INFO, LogPriority::normal);
        foreach ($fields as $name => $value) {
            $log->addLogField((string) $name, (string) $value);
        }
        $this->activityLogRepository->save($log, true);

        if ($createdAt !== null) {
            $log->setCreatedAt($createdAt);
            $this->getManager()->flush();
        }

        return $log;
    }
}
