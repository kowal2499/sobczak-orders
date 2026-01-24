<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123150546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agreement_line_rm (agreement_line_id INT NOT NULL, agreement_id INT NOT NULL, customer_id INT NOT NULL, status SMALLINT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, is_archived TINYINT(1) NOT NULL, agreement_create_date DATETIME NOT NULL, confirmed_date DATETIME NOT NULL, production_start_date DATETIME DEFAULT NULL, production_end_date DATETIME DEFAULT NULL, user_name VARCHAR(50) DEFAULT NULL, order_number VARCHAR(64) NOT NULL, customer_name VARCHAR(126) NOT NULL, product_name VARCHAR(64) DEFAULT NULL, description LONGTEXT DEFAULT NULL, factor DOUBLE PRECISION DEFAULT NULL, user LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', customer LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', product LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', agreement LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', productions LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', tags LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', attachments LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(agreement_line_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE agreement_line_rm');
    }
}
