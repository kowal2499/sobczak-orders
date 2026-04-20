<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329131932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add BaseTask fields to task table (is_start_delayed, is_completed, completed_at, created_at, updated_at)';
    }

    public function up(Schema $schema): void
    {
        // Zmiany dla tabeli task - dodanie nowych kolumn z BaseTask

        // Najpierw dodaj kolumny jako nullable
        $this->addSql('ALTER TABLE task ADD is_start_delayed TINYINT(1) DEFAULT NULL, ADD is_completed TINYINT(1) DEFAULT NULL, ADD completed_at DATETIME DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE is_deleted is_deleted TINYINT(1) NOT NULL');

        // Ustaw wartości domyślne dla istniejących rekordów
        $this->addSql('UPDATE task SET created_at = create_date WHERE created_at IS NULL');
        $this->addSql('UPDATE task SET updated_at = create_date WHERE updated_at IS NULL');

        // Usuń starą kolumnę i ustaw NOT NULL dla nowych
        $this->addSql('ALTER TABLE task DROP create_date');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE task CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Cofnięcie zmian dla tabeli task
        $this->addSql('ALTER TABLE task ADD create_date DATETIME NOT NULL, DROP is_start_delayed, DROP is_completed, DROP completed_at, DROP created_at, DROP updated_at, CHANGE is_deleted is_deleted TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
