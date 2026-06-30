<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add new grant reports.calendar_orders for the orders calendar
 * ("Kalendarz zamówień"), alongside the existing calendar grants.
 */
final class Version20260629120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reports.calendar_orders grant for the orders calendar';
    }

    public function up(Schema $schema): void
    {
        // Copy module_id, type and options from an existing calendar grant so we do
        // not assume the storage format of those columns.
        $this->addSql(
            "INSERT INTO auth_grant (module_id, slug, name, description, type, options)
             SELECT module_id,
                    'reports.calendar_orders',
                    'Kalendarz zamówień',
                    'Dostęp do kalendarza zamówień',
                    type,
                    options
             FROM auth_grant
             WHERE slug = 'reports.calendar_general'"
        );
    }

    public function down(Schema $schema): void
    {
        // Remove dependent grant values first, then the grant itself.
        $this->addSql(
            "DELETE FROM auth_role_grant_value
             WHERE grant_id IN (SELECT id FROM (SELECT id FROM auth_grant WHERE slug = 'reports.calendar_orders') AS g)"
        );
        $this->addSql(
            "DELETE FROM auth_user_grant_value
             WHERE grant_id IN (SELECT id FROM (SELECT id FROM auth_grant WHERE slug = 'reports.calendar_orders') AS g)"
        );
        $this->addSql("DELETE FROM auth_grant WHERE slug = 'reports.calendar_orders'");
    }
}
