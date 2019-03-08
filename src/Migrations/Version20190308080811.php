<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190308080811 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cached_sync_object CHANGE transfer_wise_id transfer_wise_id VARCHAR(255) NOT NULL, CHANGE smart_accounts_id smart_accounts_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE job ADD sync_record_id INT NOT NULL, ADD added INT DEFAULT NULL, ADD skipped INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F8916A6A7D FOREIGN KEY (sync_record_id) REFERENCES sync_record (id)');
        $this->addSql('CREATE INDEX IDX_FBD8E0F8916A6A7D ON job (sync_record_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cached_sync_object CHANGE transfer_wise_id transfer_wise_id BIGINT NOT NULL, CHANGE smart_accounts_id smart_accounts_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE job DROP FOREIGN KEY FK_FBD8E0F8916A6A7D');
        $this->addSql('DROP INDEX IDX_FBD8E0F8916A6A7D ON job');
        $this->addSql('ALTER TABLE job DROP sync_record_id, DROP added, DROP skipped');
    }
}
