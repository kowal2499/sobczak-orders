<?php

namespace App\Utilities;

use Symfony\Component\HttpFoundation\Response;

trait DateValidationTrait
{
    /**
     * Validates start and end date parameters from request query.
     *
     * @param string|null $startStr Start date string in Y-m-d format
     * @param string|null $endStr End date string in Y-m-d format
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}|Response Array with start and end dates, or error Response
     */
    protected function validateDateRange(?string $startStr, ?string $endStr): array|Response
    {
        try {
            if (!$startStr && !$endStr) {
                throw new \InvalidArgumentException('startDate and endDate are required');
            }

            $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $startStr) ?: null;
            $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $endStr) ?: null;

            if ($start && $start->format('Y-m-d') !== $startStr) {
                throw new \InvalidArgumentException('Invalid startDate format. Expected Y-m-d');
            }
            if ($end && $end->format('Y-m-d') !== $endStr) {
                throw new \InvalidArgumentException('Invalid endDate format. Expected Y-m-d');
            }

            if (($start && $end) && $start > $end) {
                throw new \InvalidArgumentException('startDate must be before endDate');
            }

            return ['start' => $start, 'end' => $end];
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

