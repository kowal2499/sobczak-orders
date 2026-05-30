<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_log and activity_log_field tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            type VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            level VARCHAR(10) NOT NULL,
            priority VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_activity_log_user_id (user_id),
            INDEX idx_activity_log_type (type),
            INDEX idx_activity_log_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE activity_log
            ADD CONSTRAINT FK_activity_log_user_id
            FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE activity_log_field (
            id INT AUTO_INCREMENT NOT NULL,
            activity_log_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            value LONGTEXT NOT NULL,
            INDEX idx_activity_log_field_log_id (activity_log_id),
            INDEX idx_activity_log_field_name (name),
            UNIQUE INDEX uq_activity_log_field_log_name (activity_log_id, name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE activity_log_field
            ADD CONSTRAINT FK_activity_log_field_log_id
            FOREIGN KEY (activity_log_id) REFERENCES activity_log (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log_field DROP FOREIGN KEY FK_activity_log_field_log_id');
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_activity_log_user_id');
        $this->addSql('DROP TABLE activity_log_field');
        $this->addSql('DROP TABLE activity_log');
    }
}
