<?php

namespace App\Module\Agreement\Service;

use DateTimeInterface;

/**
 * Maps an array-hydrated AgreementLineRM row to the legacy AgreementLine
 * serialization shape consumed by the orders list (OrdersList.vue / LineActions.vue).
 *
 * This lets the orders list read from the denormalised read model (one indexed
 * table, no N+1) without changing the frontend payload contract.
 */
class LegacyAgreementLineMapper
{
    public function mapRow(array $row): array
    {
        return [
            'id' => $row['agreementLineId'] ?? null,
            'confirmedDate' => $this->formatDate($row['confirmedDate'] ?? null),
            'status' => $row['status'] ?? null,
            'description' => $row['description'] ?? null,
            'factor' => $row['factor'] ?? null,
            'productionCompletionDate' => $this->formatDate($row['productionEndDate'] ?? null),
            'tags' => array_map(
                static fn (array $tag) => [
                    'tagDefinition' => [
                        'name' => $tag['name'] ?? null,
                        'icon' => $tag['icon'] ?? null,
                        'color' => $tag['color'] ?? null,
                    ],
                ],
                array_values($row['tags'] ?? [])
            ),
            'productions' => array_map(
                static fn (array $production) => [
                    'id' => $production['id'] ?? null,
                    'departmentSlug' => $production['departmentSlug'] ?? null,
                    'status' => $production['status'] ?? null,
                    'isGhost' => $production['isGhost'] ?? false,
                ],
                array_values($row['productions'] ?? [])
            ),
            'Product' => [
                'name' => $row['productName'] ?? ($row['product']['name'] ?? null),
            ],
            'Agreement' => [
                'id' => $row['agreementId'] ?? ($row['agreement']['id'] ?? null),
                'orderNumber' => $row['orderNumber'] ?? null,
                'createDate' => $this->formatDate($row['agreementCreateDate'] ?? null),
                'user' => [
                    'userFullName' => $row['userName'] ?? null,
                ],
                'Customer' => $this->mapCustomer($row['customer'] ?? []),
                'attachments' => array_values($row['attachments'] ?? []),
            ],
        ];
    }

    private function mapCustomer(array $customer): array
    {
        return [
            'id' => $customer['id'] ?? null,
            'name' => $customer['name'] ?? null,
            // Read model uses camelCase; the legacy frontend mixin reads snake_case.
            'first_name' => $customer['firstName'] ?? null,
            'last_name' => $customer['lastName'] ?? null,
        ];
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        // JSON columns already carry preformatted strings.
        return is_string($date) ? $date : null;
    }
}
