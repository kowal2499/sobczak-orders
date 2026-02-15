<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250502213151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auth_grant (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, slug VARCHAR(40) NOT NULL, name VARCHAR(60) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:grant_options)\', UNIQUE INDEX UNIQ_5273739989D9B62 (slug), INDEX IDX_5273739AFC2B591 (module_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, UNIQUE INDEX UNIQ_794F6ADE5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_role_grant_value (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, grant_id INT NOT NULL, grant_option_slug VARCHAR(255) DEFAULT NULL, value TINYINT(1) DEFAULT NULL, INDEX IDX_3AFD12BCD60322AC (role_id), INDEX IDX_3AFD12BC5C0C89F3 (grant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_user_grant_value (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, grant_id INT NOT NULL, grant_option_slug VARCHAR(255) DEFAULT NULL, value TINYINT(1) DEFAULT NULL, INDEX IDX_A1B183A0A76ED395 (user_id), INDEX IDX_A1B183A05C0C89F3 (grant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_user_role (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_29171A31A76ED395 (user_id), INDEX IDX_29171A31D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, namespace VARCHAR(40) NOT NULL, description LONGTEXT DEFAULT NULL, active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE INDEX UNIQ_C24262833E16B56 (namespace), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE auth_grant ADD CONSTRAINT FK_5273739AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE auth_role_grant_value ADD CONSTRAINT FK_3AFD12BCD60322AC FOREIGN KEY (role_id) REFERENCES auth_role (id)');
        $this->addSql('ALTER TABLE auth_role_grant_value ADD CONSTRAINT FK_3AFD12BC5C0C89F3 FOREIGN KEY (grant_id) REFERENCES auth_grant (id)');
        $this->addSql('ALTER TABLE auth_user_grant_value ADD CONSTRAINT FK_A1B183A0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE auth_user_grant_value ADD CONSTRAINT FK_A1B183A05C0C89F3 FOREIGN KEY (grant_id) REFERENCES auth_grant (id)');
        $this->addSql('ALTER TABLE auth_user_role ADD CONSTRAINT FK_29171A31A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE auth_user_role ADD CONSTRAINT FK_29171A31D60322AC FOREIGN KEY (role_id) REFERENCES auth_role (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auth_grant DROP FOREIGN KEY FK_5273739AFC2B591');
        $this->addSql('ALTER TABLE auth_role_grant_value DROP FOREIGN KEY FK_3AFD12BCD60322AC');
        $this->addSql('ALTER TABLE auth_role_grant_value DROP FOREIGN KEY FK_3AFD12BC5C0C89F3');
        $this->addSql('ALTER TABLE auth_user_grant_value DROP FOREIGN KEY FK_A1B183A0A76ED395');
        $this->addSql('ALTER TABLE auth_user_grant_value DROP FOREIGN KEY FK_A1B183A05C0C89F3');
        $this->addSql('ALTER TABLE auth_user_role DROP FOREIGN KEY FK_29171A31A76ED395');
        $this->addSql('ALTER TABLE auth_user_role DROP FOREIGN KEY FK_29171A31D60322AC');
        $this->addSql('DROP TABLE auth_grant');
        $this->addSql('DROP TABLE auth_role');
        $this->addSql('DROP TABLE auth_role_grant_value');
        $this->addSql('DROP TABLE auth_user_grant_value');
        $this->addSql('DROP TABLE auth_user_role');
        $this->addSql('DROP TABLE module');
    }
}
