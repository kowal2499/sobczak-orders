<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210201220110 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tag_definition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, module VARCHAR(128) NOT NULL, icon VARCHAR(128) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_assignment (id INT AUTO_INCREMENT NOT NULL, tag_definition_id INT DEFAULT NULL, user_id INT DEFAULT NULL, context_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_95DEC6EB3FB0DE16 (tag_definition_id), INDEX IDX_95DEC6EBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tag_assignment ADD CONSTRAINT FK_95DEC6EB3FB0DE16 FOREIGN KEY (tag_definition_id) REFERENCES tag_definition (id)');
        $this->addSql('ALTER TABLE tag_assignment ADD CONSTRAINT FK_95DEC6EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tag_assignment DROP FOREIGN KEY FK_95DEC6EB3FB0DE16');
        $this->addSql('DROP TABLE tag_definition');
        $this->addSql('DROP TABLE tag_assignment');
    }
}
