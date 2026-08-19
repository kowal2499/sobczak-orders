<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tabele modułu BonusSettlement: miesięczny okres rozliczeniowy premii i jego wiersze
 * (jeden wiersz na parę pracownik + dział).
 */
final class Version20260819120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bonus_period and bonus_period_entry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE bonus_period ('
            . 'id INT AUTO_INCREMENT NOT NULL, '
            . 'closed_by INT DEFAULT NULL, '
            . 'year SMALLINT NOT NULL, '
            . 'month SMALLINT NOT NULL, '
            . 'status VARCHAR(16) NOT NULL, '
            . 'tolerance_days SMALLINT NOT NULL, '
            . "calculated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . "closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'INDEX IDX_8AB2541F88F6E01 (closed_by), '
            . 'UNIQUE INDEX unique_bonus_period_year_month (year, month), '
            . 'PRIMARY KEY(id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE bonus_period_entry ('
            . 'id INT AUTO_INCREMENT NOT NULL, '
            . 'period_id INT NOT NULL, '
            . 'user_id INT NOT NULL, '
            . 'user_label VARCHAR(255) NOT NULL, '
            . 'department_slug VARCHAR(10) NOT NULL, '
            . 'department_label VARCHAR(255) NOT NULL, '
            . 'factors_calculated DOUBLE PRECISION NOT NULL, '
            . 'factors_adjusted DOUBLE PRECISION DEFAULT NULL, '
            . 'note LONGTEXT DEFAULT NULL, '
            . "adjusted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'INDEX IDX_C5D3B0EEEC8B7ADE (period_id), '
            . 'INDEX IDX_C5D3B0EEA76ED395 (user_id), '
            . 'UNIQUE INDEX unique_bonus_entry_period_user_department (period_id, user_id, department_slug), '
            . 'PRIMARY KEY(id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE bonus_period ADD CONSTRAINT FK_8AB2541F88F6E01 '
            . 'FOREIGN KEY (closed_by) REFERENCES user (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE bonus_period_entry ADD CONSTRAINT FK_C5D3B0EEEC8B7ADE '
            . 'FOREIGN KEY (period_id) REFERENCES bonus_period (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE bonus_period_entry ADD CONSTRAINT FK_C5D3B0EEA76ED395 '
            . 'FOREIGN KEY (user_id) REFERENCES user (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bonus_period_entry DROP FOREIGN KEY FK_C5D3B0EEEC8B7ADE');
        $this->addSql('ALTER TABLE bonus_period_entry DROP FOREIGN KEY FK_C5D3B0EEA76ED395');
        $this->addSql('ALTER TABLE bonus_period DROP FOREIGN KEY FK_8AB2541F88F6E01');
        $this->addSql('DROP TABLE bonus_period_entry');
        $this->addSql('DROP TABLE bonus_period');
    }
}
