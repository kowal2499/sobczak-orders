<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add dpt06 (INTOREX) columns to agreement_line_rm table
 */
final class Version20260313100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dpt06_start_date and dpt06_end_date columns to agreement_line_rm table for INTOREX department';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agreement_line_rm ADD dpt06_start_date DATETIME DEFAULT NULL AFTER dpt05_end_date');
        $this->addSql('ALTER TABLE agreement_line_rm ADD dpt06_end_date DATETIME DEFAULT NULL AFTER dpt06_start_date');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agreement_line_rm DROP dpt06_start_date');
        $this->addSql('ALTER TABLE agreement_line_rm DROP dpt06_end_date');
    }
}
