<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_ghost flag to production for forecast tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production ADD is_ghost TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_production_department_date_end_ghost ON production (department_slug, date_end, is_ghost)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_production_department_date_end_ghost ON production');
        $this->addSql('ALTER TABLE production DROP is_ghost');
    }
}
