<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119211958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agreement_line_rm table with JSON columns and all necessary indexes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agreement_line_rm (agreement_line_id INT NOT NULL, agreement_id INT NOT NULL, customer_id INT NOT NULL, status SMALLINT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, is_archived TINYINT(1) NOT NULL, agreement_create_date DATETIME NOT NULL, confirmed_date DATETIME NOT NULL, production_start_date DATETIME DEFAULT NULL, production_end_date DATETIME DEFAULT NULL, user_data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', customer_data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', product_data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', agreement_data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', productions_data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(agreement_line_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Single column indexes
        $this->addSql('CREATE INDEX idx_agreement_id ON agreement_line_rm (agreement_id)');
        $this->addSql('CREATE INDEX idx_customer_id ON agreement_line_rm (customer_id)');
        $this->addSql('CREATE INDEX idx_status ON agreement_line_rm (status)');
        $this->addSql('CREATE INDEX idx_is_deleted ON agreement_line_rm (is_deleted)');
        $this->addSql('CREATE INDEX idx_is_archived ON agreement_line_rm (is_archived)');
        $this->addSql('CREATE INDEX idx_confirmed_date ON agreement_line_rm (confirmed_date)');
        $this->addSql('CREATE INDEX idx_production_start_date ON agreement_line_rm (production_start_date)');
        $this->addSql('CREATE INDEX idx_production_end_date ON agreement_line_rm (production_end_date)');

        // Composite indexes for common queries (commented out for now)
        // $this->addSql('CREATE INDEX idx_customer_deleted ON agreement_line_rm (customer_id, is_deleted)');
        // $this->addSql('CREATE INDEX idx_customer_archived ON agreement_line_rm (customer_id, is_archived)');
        // $this->addSql('CREATE INDEX idx_status_confirmed ON agreement_line_rm (status, confirmed_date)');
        // $this->addSql('CREATE INDEX idx_agreement_deleted ON agreement_line_rm (agreement_id, is_deleted)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes before dropping table
        $this->addSql('DROP INDEX idx_agreement_id ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_customer_id ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_status ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_is_deleted ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_is_archived ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_confirmed_date ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_production_start_date ON agreement_line_rm');
        $this->addSql('DROP INDEX idx_production_end_date ON agreement_line_rm');

        // Composite indexes (commented out)
        // $this->addSql('DROP INDEX idx_customer_deleted ON agreement_line_rm');
        // $this->addSql('DROP INDEX idx_customer_archived ON agreement_line_rm');
        // $this->addSql('DROP INDEX idx_status_confirmed ON agreement_line_rm');
        // $this->addSql('DROP INDEX idx_agreement_deleted ON agreement_line_rm');

        $this->addSql('DROP TABLE agreement_line_rm');
    }
}
