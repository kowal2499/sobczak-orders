<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115192715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agreement_line_rm (id INT NOT NULL, status SMALLINT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, is_archived TINYINT(1) NOT NULL, confirmed_date DATETIME NOT NULL, production_start_date DATETIME DEFAULT NULL, production_end_date DATETIME DEFAULT NULL, user_id INT DEFAULT NULL, user_first_name VARCHAR(255) DEFAULT NULL, user_last_name VARCHAR(255) DEFAULT NULL, user_email VARCHAR(180) DEFAULT NULL, customer_id INT NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_first_name VARCHAR(255) DEFAULT NULL, customer_last_name VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(255) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_address_street VARCHAR(255) DEFAULT NULL, customer_address_street_number VARCHAR(8) DEFAULT NULL, customer_address_apartment_number VARCHAR(8) DEFAULT NULL, customer_address_postal_code VARCHAR(16) DEFAULT NULL, customer_address_city VARCHAR(16) DEFAULT NULL, customer_address_country VARCHAR(2) DEFAULT NULL, product_id INT NOT NULL, product_name VARCHAR(255) NOT NULL, product_factor DOUBLE PRECISION NOT NULL, agreement_id INT NOT NULL, agreement_status VARCHAR(64) DEFAULT NULL, agreement_order_number VARCHAR(255) DEFAULT NULL, agreement_created_date DATETIME NOT NULL, dpt01_id INT DEFAULT NULL, dpt01_department_slug VARCHAR(255) NOT NULL, dpt01_date_start DATETIME DEFAULT NULL, dpt01_date_end DATETIME DEFAULT NULL, dpt01_status VARCHAR(64) DEFAULT NULL, dpt01_is_start_delayed TINYINT(1) DEFAULT NULL, dpt01_is_completed TINYINT(1) DEFAULT NULL, dpt01_completed_at DATETIME DEFAULT NULL, dpt01_factor_ratio DOUBLE PRECISION DEFAULT NULL, dpt01_factor_bonus DOUBLE PRECISION DEFAULT NULL, dpt02_id INT DEFAULT NULL, dpt02_department_slug VARCHAR(255) NOT NULL, dpt02_date_start DATETIME DEFAULT NULL, dpt02_date_end DATETIME DEFAULT NULL, dpt02_status VARCHAR(64) DEFAULT NULL, dpt02_is_start_delayed TINYINT(1) DEFAULT NULL, dpt02_is_completed TINYINT(1) DEFAULT NULL, dpt02_completed_at DATETIME DEFAULT NULL, dpt02_factor_ratio DOUBLE PRECISION DEFAULT NULL, dpt02_factor_bonus DOUBLE PRECISION DEFAULT NULL, dpt03_id INT DEFAULT NULL, dpt03_department_slug VARCHAR(255) NOT NULL, dpt03_date_start DATETIME DEFAULT NULL, dpt03_date_end DATETIME DEFAULT NULL, dpt03_status VARCHAR(64) DEFAULT NULL, dpt03_is_start_delayed TINYINT(1) DEFAULT NULL, dpt03_is_completed TINYINT(1) DEFAULT NULL, dpt03_completed_at DATETIME DEFAULT NULL, dpt03_factor_ratio DOUBLE PRECISION DEFAULT NULL, dpt03_factor_bonus DOUBLE PRECISION DEFAULT NULL, dpt04_id INT DEFAULT NULL, dpt04_department_slug VARCHAR(255) NOT NULL, dpt04_date_start DATETIME DEFAULT NULL, dpt04_date_end DATETIME DEFAULT NULL, dpt04_status VARCHAR(64) DEFAULT NULL, dpt04_is_start_delayed TINYINT(1) DEFAULT NULL, dpt04_is_completed TINYINT(1) DEFAULT NULL, dpt04_completed_at DATETIME DEFAULT NULL, dpt04_factor_ratio DOUBLE PRECISION DEFAULT NULL, dpt04_factor_bonus DOUBLE PRECISION DEFAULT NULL, dpt05_id INT DEFAULT NULL, dpt05_department_slug VARCHAR(255) NOT NULL, dpt05_date_start DATETIME DEFAULT NULL, dpt05_date_end DATETIME DEFAULT NULL, dpt05_status VARCHAR(64) DEFAULT NULL, dpt05_is_start_delayed TINYINT(1) DEFAULT NULL, dpt05_is_completed TINYINT(1) DEFAULT NULL, dpt05_completed_at DATETIME DEFAULT NULL, dpt05_factor_ratio DOUBLE PRECISION DEFAULT NULL, dpt05_factor_bonus DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE agreement_line_rm');
    }
}
