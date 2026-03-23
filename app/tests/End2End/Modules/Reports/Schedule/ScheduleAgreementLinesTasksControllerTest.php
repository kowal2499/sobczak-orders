<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use App\Entity\AgreementLine;

class ScheduleAgreementLinesTasksControllerTest extends BaseScheduleReportsTestCase
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

    public function testShouldReturnAgreementLinesTasksForDateRange()
    {
        // Given
        $em = $this->getManager();
        $user = $this->createUser([], [], []);
        $client = $this->login($user);

        $this->createAgreementLineRM(
            1,
            'ORD-1',
            new \DateTime('2021-09-10'),
            AgreementLine::STATUS_MANUFACTURING,
            1.0,
            false,
            false,
            true
        );
        $em->flush();
        $em->clear();

        // When
        $client->xmlHttpRequest('GET', '/reports/schedule/agreement-lines?startDate=2021-09-01&endDate=2021-09-30');

        // Then
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('agreementLineId', $data[0]);
        $this->assertArrayHasKey('hasProduction', $data[0]);
        $this->assertEquals(true, $data[0]['hasProduction']);
    }
}
