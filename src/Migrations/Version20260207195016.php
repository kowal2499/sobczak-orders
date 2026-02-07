<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207195016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE working_schedule ADD day_type VARCHAR(16) NOT NULL, ADD description VARCHAR(255) DEFAULT NULL, DROP time_start, DROP time_end, DROP is_working');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE working_schedule ADD time_start TIME DEFAULT NULL, ADD time_end TIME DEFAULT NULL, ADD is_working TINYINT(1) NOT NULL, DROP day_type, DROP description');
    }
}
