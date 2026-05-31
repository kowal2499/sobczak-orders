<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename grant reports.production_calendar -> reports.calendar_general (keeps row id,
 * so existing role/user grant values referencing it via FK are preserved)
 * and add new grant reports.calendar_tasks for the tasks calendar.
 */
final class Version20260531090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split calendar grant: rename reports.production_calendar to reports.calendar_general and add reports.calendar_tasks';
    }

    public function up(Schema $schema): void
    {
        // Rename existing grant in place (preserves id and all FK grant values).
        $this->addSql(
            "UPDATE auth_grant
             SET slug = 'reports.calendar_general',
                 name = 'Kalendarz ogólny',
                 description = 'Dostęp do kalendarza ogólnego'
             WHERE slug = 'reports.production_calendar'"
        );

        // Add new grant for the tasks calendar, copying module_id, type and options
        // from the renamed row so we do not assume the storage format of those columns.
        $this->addSql(
            "INSERT INTO auth_grant (module_id, slug, name, description, type, options)
             SELECT module_id,
                    'reports.calendar_tasks',
                    'Kalendarz zadań',
                    'Dostęp do kalendarza zadań',
                    type,
                    options
             FROM auth_grant
             WHERE slug = 'reports.calendar_general'"
        );
    }

    public function down(Schema $schema): void
    {
        // Remove dependent grant values for the tasks grant first, then the grant itself.
        $this->addSql(
            "DELETE FROM auth_role_grant_value
             WHERE grant_id IN (SELECT id FROM (SELECT id FROM auth_grant WHERE slug = 'reports.calendar_tasks') AS g)"
        );
        $this->addSql(
            "DELETE FROM auth_user_grant_value
             WHERE grant_id IN (SELECT id FROM (SELECT id FROM auth_grant WHERE slug = 'reports.calendar_tasks') AS g)"
        );
        $this->addSql("DELETE FROM auth_grant WHERE slug = 'reports.calendar_tasks'");

        // Revert the rename.
        $this->addSql(
            "UPDATE auth_grant
             SET slug = 'reports.production_calendar',
                 name = 'Kalendarz produkcji',
                 description = 'Dostęp do kalendarza produkcji'
             WHERE slug = 'reports.calendar_general'"
        );
    }
}
