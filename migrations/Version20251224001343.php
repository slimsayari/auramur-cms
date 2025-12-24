<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224001343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_provider_configs (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, provider VARCHAR(50) NOT NULL, api_key CLOB NOT NULL, api_secret CLOB DEFAULT NULL, model VARCHAR(100) DEFAULT NULL, default_prompt CLOB DEFAULT NULL, image_size VARCHAR(50) DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE wordpress_import_logs (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , source VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, articles_imported INTEGER NOT NULL, images_imported INTEGER NOT NULL, categories_imported INTEGER NOT NULL, tags_imported INTEGER NOT NULL, errors CLOB NOT NULL --(DC2Type:json)
        , metadata CLOB NOT NULL --(DC2Type:json)
        , imported_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , completed_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ai_provider_configs');
        $this->addSql('DROP TABLE wordpress_import_logs');
    }
}
