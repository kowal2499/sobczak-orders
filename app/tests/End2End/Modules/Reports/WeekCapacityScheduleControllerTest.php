<?php

namespace App\Tests\End2End\Modules\Reports;

use App\System\Test\ApiTestCase;

class WeekCapacityScheduleControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldRoundDaysUpToThisWeekMondayAndThisWeekSunday(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $client = $this->login($user);

        // When
        $client->xmlHttpRequest('GET', '/reports/production/week-capacity-schedule?startDate=2025-12-29&endDate=2026-01-31');

        // Then
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('2025-12-29', $content[0]['start']);
        $this->assertEquals('2026-02-01', array_pop($content)['end']);

        // todo: zweryfikować na faktycznyc danych zamówień
//        [
//            [
//                'weekNumber' => 1,
//                'start' => '2025-12-29',
//                'end' => '2026-01-04',
//                'capacity' => 0,
//                'capacityUsed' => 0,
//                'orders' => []
//            ]
//        ];

    }
}