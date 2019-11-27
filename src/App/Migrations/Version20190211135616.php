<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190211135616 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cached_sync_object (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, transfer_wise_id BIGINT NOT NULL, smart_accounts_id BIGINT DEFAULT NULL, INDEX IDX_E05B960DBE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, started DATETIME NOT NULL, finished DATETIME DEFAULT NULL, log LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sync_record (id INT AUTO_INCREMENT NOT NULL, smart_accounts_api_key_public VARCHAR(255) NOT NULL, smart_accounts_api_key_private VARCHAR(255) NOT NULL, transfer_wise_api_token VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cached_sync_object ADD CONSTRAINT FK_E05B960DBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cached_sync_object DROP FOREIGN KEY FK_E05B960DBE04EA9');
        $this->addSql('DROP TABLE cached_sync_object');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE sync_record');
    }
}
