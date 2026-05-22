<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add content_params (JSON) column to activity_log for i18n interpolation parameters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log ADD content_params LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log DROP content_params');
    }
}
