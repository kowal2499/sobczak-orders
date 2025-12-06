<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206204131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_factor_adjustment DROP FOREIGN KEY FK_FC0E6521ECC6147F');
        $this->addSql('DROP INDEX idx_fc0e6521ecc6147f ON production_factor_adjustment');
        $this->addSql('CREATE INDEX IDX_7618582CECC6147F ON production_factor_adjustment (production_id)');
        $this->addSql('ALTER TABLE production_factor_adjustment ADD CONSTRAINT FK_FC0E6521ECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_factor_adjustment DROP FOREIGN KEY FK_7618582CECC6147F');
        $this->addSql('DROP INDEX idx_7618582cecc6147f ON production_factor_adjustment');
        $this->addSql('CREATE INDEX IDX_FC0E6521ECC6147F ON production_factor_adjustment (production_id)');
        $this->addSql('ALTER TABLE production_factor_adjustment ADD CONSTRAINT FK_7618582CECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
    }
}
