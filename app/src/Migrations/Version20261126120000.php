<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index production.completed_at for the completed-tasks dashboard metrics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_production_completed_at ON production (completed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_production_completed_at ON production');
    }
}
