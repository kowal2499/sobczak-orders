<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_status_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_status_log (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            previous_status SMALLINT DEFAULT NULL,
            current_status SMALLINT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_task_status_log_task_id (task_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE task_status_log
            ADD CONSTRAINT FK_task_status_log_task_id
            FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE task_status_log
            ADD CONSTRAINT FK_task_status_log_user_id
            FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_status_log DROP FOREIGN KEY FK_task_status_log_task_id');
        $this->addSql('ALTER TABLE task_status_log DROP FOREIGN KEY FK_task_status_log_user_id');
        $this->addSql('DROP TABLE task_status_log');
    }
}
