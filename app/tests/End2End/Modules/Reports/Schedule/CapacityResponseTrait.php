<?php

namespace App\Tests\End2End\Modules\Reports\Schedule;

use Symfony\Component\HttpFoundation\Response;

trait CapacityResponseTrait
{
    /**
     * Same expansion as expandCapacityDays() on the front: each day gets its week's lines.
     *
     * @return array<int, array<string, mixed>>
     */
    private function capacityDays(Response $response): array
    {
        $payload = json_decode($response->getContent(), true);

        return array_map(
            fn (array $day) => $day + ['agreementLines' => $payload['agreementLinesByWeek'][$day['weekStart']] ?? []],
            $payload['days']
        );
    }
}
