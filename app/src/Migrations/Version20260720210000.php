<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add per-line internal number (suffix) to AgreementLine and its read model,
 * so orders with multiple lines can display a unique number per line
 * (orderNumber + '-' + internalNumber).
 */
final class Version20260720210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add internal_number to agreement_line and agreement_line_rm';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agreement_line ADD internal_number VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE agreement_line_rm ADD internal_number VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agreement_line DROP internal_number');
        $this->addSql('ALTER TABLE agreement_line_rm DROP internal_number');
    }
}
