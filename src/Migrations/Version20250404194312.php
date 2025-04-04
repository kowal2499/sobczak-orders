<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404194312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `grant` (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, slug VARCHAR(40) NOT NULL, name VARCHAR(60) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:grant_options)\', INDEX IDX_C905664CAFC2B591 (module_id), UNIQUE INDEX UNIQ_C905664C989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, namespace VARCHAR(40) NOT NULL, UNIQUE INDEX UNIQ_C24262833E16B56 (namespace), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, UNIQUE INDEX UNIQ_57698A6A5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_grant_value (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, grant_id INT NOT NULL, value LONGTEXT NOT NULL COMMENT \'(DC2Type:grant_value)\', INDEX IDX_6250F926D60322AC (role_id), INDEX IDX_6250F9265C0C89F3 (grant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_grant_value (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, grant_id INT NOT NULL, value LONGTEXT NOT NULL COMMENT \'(DC2Type:grant_value)\', INDEX IDX_F91C683AA76ED395 (user_id), INDEX IDX_F91C683A5C0C89F3 (grant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `grant` ADD CONSTRAINT FK_C905664CAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE role_grant_value ADD CONSTRAINT FK_6250F926D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_grant_value ADD CONSTRAINT FK_6250F9265C0C89F3 FOREIGN KEY (grant_id) REFERENCES `grant` (id)');
        $this->addSql('ALTER TABLE user_grant_value ADD CONSTRAINT FK_F91C683AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_grant_value ADD CONSTRAINT FK_F91C683A5C0C89F3 FOREIGN KEY (grant_id) REFERENCES `grant` (id)');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('DROP TABLE migration_versions');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE migration_versions (version VARCHAR(14) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE `grant` DROP FOREIGN KEY FK_C905664CAFC2B591');
        $this->addSql('ALTER TABLE role_grant_value DROP FOREIGN KEY FK_6250F926D60322AC');
        $this->addSql('ALTER TABLE role_grant_value DROP FOREIGN KEY FK_6250F9265C0C89F3');
        $this->addSql('ALTER TABLE user_grant_value DROP FOREIGN KEY FK_F91C683AA76ED395');
        $this->addSql('ALTER TABLE user_grant_value DROP FOREIGN KEY FK_F91C683A5C0C89F3');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE `grant`');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_grant_value');
        $this->addSql('DROP TABLE user_grant_value');
        $this->addSql('DROP TABLE user_role');
    }
}
