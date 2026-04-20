<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task table for Task module';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task (
            id INT AUTO_INCREMENT NOT NULL,
            owner_id INT DEFAULT NULL,
            agreement_line_id INT NOT NULL,
            date_start DATETIME DEFAULT NULL,
            date_end DATETIME DEFAULT NULL,
            status SMALLINT NOT NULL,
            type VARCHAR(64) NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            is_deleted TINYINT(1) NOT NULL DEFAULT 0,
            create_date DATETIME NOT NULL,
            INDEX idx_task_agreement_line_id (agreement_line_id),
            INDEX idx_task_owner_id (owner_id),
            INDEX idx_task_status (status),
            INDEX idx_task_is_deleted (is_deleted),
            INDEX idx_task_type (type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_task_owner_id FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_task_agreement_line_id FOREIGN KEY (agreement_line_id) REFERENCES agreement_line (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_task_owner_id');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_task_agreement_line_id');
        $this->addSql('DROP TABLE task');
    }
}
