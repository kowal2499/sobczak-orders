<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190707063850 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE production (id INT AUTO_INCREMENT NOT NULL, agreement_line_id INT NOT NULL, department_slug VARCHAR(255) NOT NULL, date_start DATETIME DEFAULT NULL, date_end DATETIME DEFAULT NULL, status VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D3EDB1E0E56241E6 (agreement_line_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production ADD CONSTRAINT FK_D3EDB1E0E56241E6 FOREIGN KEY (agreement_line_id) REFERENCES agreement_line (id)');

//        $this->addSql('ALTER TABLE agreement RENAME INDEX idx_f52993989395c3f3 TO IDX_2E655A249395C3F3');
        $this->addSql('ALTER TABLE agreement ADD INDEX IDX_2E655A249395C3F3 (customer_id)');
        $this->addSql('ALTER TABLE agreement DROP INDEX idx_f52993989395c3f3');


//        $this->addSql('ALTER TABLE agreement_line RENAME INDEX idx_9ce58ee14584665a TO IDX_59DBA2084584665A');
        $this->addSql('ALTER TABLE agreement_line ADD INDEX IDX_59DBA2084584665A (product_id)');
        $this->addSql('ALTER TABLE agreement_line DROP INDEX idx_9ce58ee14584665a');

//        $this->addSql('ALTER TABLE agreement_line RENAME INDEX idx_9ce58ee1251a8a50 TO IDX_59DBA20824890B2B');
        $this->addSql('ALTER TABLE agreement_line ADD INDEX IDX_59DBA20824890B2B (agreement_id)');
        $this->addSql('ALTER TABLE agreement_line DROP INDEX idx_9ce58ee1251a8a50');


        $this->addSql('ALTER TABLE customer CHANGE first_name first_name VARCHAR(255) DEFAULT NULL, CHANGE last_name last_name VARCHAR(255) DEFAULT NULL, CHANGE street street VARCHAR(255) DEFAULT NULL, CHANGE street_number street_number VARCHAR(255) DEFAULT NULL, CHANGE apartment_number apartment_number VARCHAR(255) DEFAULT NULL, CHANGE city city VARCHAR(255) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(16) DEFAULT NULL, CHANGE country country VARCHAR(2) DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE production');
//        $this->addSql('ALTER TABLE agreement RENAME INDEX idx_2e655a249395c3f3 TO IDX_F52993989395C3F3');
        $this->addSql('ALTER TABLE agreement ADD INDEX IDX_F52993989395C3F3');
        $this->addSql('ALTER TABLE agreement DROP INDEX IDX_2E655A249395C3F3');

//        $this->addSql('ALTER TABLE agreement_line RENAME INDEX idx_59dba2084584665a TO IDX_9CE58EE14584665A');
        $this->addSql('ALTER TABLE agreement ADD INDEX IDX_9CE58EE14584665A');
        $this->addSql('ALTER TABLE agreement DROP INDEX IDX_59DBA2084584665A');

//        $this->addSql('ALTER TABLE agreement_line RENAME INDEX idx_59dba20824890b2b TO IDX_9CE58EE1251A8A50');
        $this->addSql('ALTER TABLE agreement ADD INDEX IDX_9CE58EE1251A8A50');
        $this->addSql('ALTER TABLE agreement DROP INDEX IDX_59DBA20824890B2B');


        $this->addSql('ALTER TABLE customer CHANGE first_name first_name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE last_name last_name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE street street VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE street_number street_number VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE apartment_number apartment_number VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE city city VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE postal_code postal_code VARCHAR(16) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE country country VARCHAR(2) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE phone phone VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE email email VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
