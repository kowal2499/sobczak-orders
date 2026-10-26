<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create metric_snapshot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE metric_snapshot ('
            . 'id INT AUTO_INCREMENT NOT NULL, '
            . "snapshot_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', "
            . 'metric VARCHAR(32) NOT NULL, '
            . 'dimension VARCHAR(64) NOT NULL, '
            . 'period_key VARCHAR(16) NOT NULL, '
            . 'customer_id INT NOT NULL, '
            . 'factor_sum DOUBLE PRECISION DEFAULT NULL, '
            . 'item_count INT NOT NULL, '
            . "params JSON DEFAULT NULL COMMENT '(DC2Type:json)', "
            . "computed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'source VARCHAR(16) NOT NULL, '
            . 'UNIQUE INDEX unique_metric_snapshot (snapshot_date, metric, dimension, period_key, customer_id), '
            . 'PRIMARY KEY(id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE metric_snapshot');
    }
}
