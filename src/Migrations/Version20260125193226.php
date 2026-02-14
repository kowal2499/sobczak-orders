<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125193226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agreement_line_rm (agreement_line_id INT NOT NULL, agreement_id INT NOT NULL, customer_id INT NOT NULL, status SMALLINT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, is_archived TINYINT(1) NOT NULL, has_production TINYINT(1) NOT NULL, agreement_create_date DATETIME NOT NULL, confirmed_date DATETIME NOT NULL, production_start_date DATETIME DEFAULT NULL, production_end_date DATETIME DEFAULT NULL, user_name VARCHAR(50) DEFAULT NULL, order_number VARCHAR(64) NOT NULL, customer_name VARCHAR(126) NOT NULL, product_name VARCHAR(64) DEFAULT NULL, description LONGTEXT DEFAULT NULL, factor DOUBLE PRECISION DEFAULT NULL, dpt01_start_date DATETIME DEFAULT NULL, dpt01_end_date DATETIME DEFAULT NULL, dpt02_start_date DATETIME DEFAULT NULL, dpt02_end_date DATETIME DEFAULT NULL, dpt03_start_date DATETIME DEFAULT NULL, dpt03_end_date DATETIME DEFAULT NULL, dpt04_start_date DATETIME DEFAULT NULL, dpt04_end_date DATETIME DEFAULT NULL, dpt05_start_date DATETIME DEFAULT NULL, dpt05_end_date DATETIME DEFAULT NULL, user LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', customer LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', product LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', agreement LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', productions LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', tags LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', attachments LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', q LONGTEXT NOT NULL, PRIMARY KEY(agreement_line_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // DODANE INDEKSY (na podstawie kolumn w tabeli oraz typowych zapytań filtrujących/sortujących)
        $this->addSql('CREATE INDEX idx_agreement_line_rm_agreement_id ON agreement_line_rm (agreement_id)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_customer_id ON agreement_line_rm (customer_id)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_status ON agreement_line_rm (status)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_is_deleted ON agreement_line_rm (is_deleted)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_is_archived ON agreement_line_rm (is_archived)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_has_production ON agreement_line_rm (has_production)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_order_number ON agreement_line_rm (order_number)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_user_name ON agreement_line_rm (user_name)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_confirmed_date ON agreement_line_rm (confirmed_date)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_production_start_date ON agreement_line_rm (production_start_date)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_production_end_date ON agreement_line_rm (production_end_date)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_agreement_create_date ON agreement_line_rm (agreement_create_date)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_customer_name ON agreement_line_rm (customer_name)');
        $this->addSql('CREATE INDEX idx_agreement_line_rm_product_name ON agreement_line_rm (product_name)');
    }

    public function down(Schema $schema): void
    {
        // Usuń indeksy przed usunięciem tabeli (opcjonalne, DROP TABLE by je usunął automatycznie)
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_product_name');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_customer_name');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_agreement_create_date');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_production_end_date');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_production_start_date');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_confirmed_date');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_user_name');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_order_number');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_has_production');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_is_archived');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_is_deleted');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_status');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_customer_id');
        $this->addSql('ALTER TABLE agreement_line_rm DROP INDEX idx_agreement_line_rm_agreement_id');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE agreement_line_rm');
    }
}
