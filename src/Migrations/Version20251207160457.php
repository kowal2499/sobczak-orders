<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207160457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE factor (id INT AUTO_INCREMENT NOT NULL, agreement_line_id INT NOT NULL, department_slug VARCHAR(255) DEFAULT NULL, source VARCHAR(255) NOT NULL, description VARCHAR(512) DEFAULT NULL, factor_value DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_ED38EC00E56241E6 (agreement_line_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE factor ADD CONSTRAINT FK_ED38EC00E56241E6 FOREIGN KEY (agreement_line_id) REFERENCES agreement_line (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE factor DROP FOREIGN KEY FK_ED38EC00E56241E6');
        $this->addSql('DROP TABLE factor');
    }
}
