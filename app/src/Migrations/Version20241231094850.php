<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241231094850 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_customer DROP FOREIGN KEY FK_61B46A099395C3F3');
        $this->addSql('ALTER TABLE user_customer DROP FOREIGN KEY FK_61B46A09A76ED395');
        $this->addSql('ALTER TABLE user_customer ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE user_customer ADD CONSTRAINT FK_61B46A099395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE user_customer ADD CONSTRAINT FK_61B46A09A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_customer MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE user_customer DROP FOREIGN KEY FK_61B46A09A76ED395');
        $this->addSql('ALTER TABLE user_customer DROP FOREIGN KEY FK_61B46A099395C3F3');
        $this->addSql('ALTER TABLE user_customer DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_customer DROP id');
        $this->addSql('ALTER TABLE user_customer ADD CONSTRAINT FK_61B46A09A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_customer ADD CONSTRAINT FK_61B46A099395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_customer ADD PRIMARY KEY (user_id, customer_id)');
    }
}
