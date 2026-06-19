<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_setting table: per-user, per-context JSON settings (e.g. dashboard layout)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_setting (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, context VARCHAR(190) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3CCB0521A76ED395 (user_id), UNIQUE INDEX unique_user_setting_context (user_id, context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_setting ADD CONSTRAINT FK_3CCB0521A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_setting DROP FOREIGN KEY FK_3CCB0521A76ED395');
        $this->addSql('DROP TABLE user_setting');
    }
}
