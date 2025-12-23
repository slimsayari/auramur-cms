<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223213101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_generations (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , product_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , article_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , type VARCHAR(255) NOT NULL, generated_content CLOB NOT NULL, status VARCHAR(255) NOT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , rejection_reason CLOB DEFAULT NULL, generated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , validated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_C6B1B3FC4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C6B1B3FC7294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C6B1B3FC4584665A ON ai_generations (product_id)');
        $this->addSql('CREATE INDEX IDX_C6B1B3FC7294869C ON ai_generations (article_id)');
        $this->addSql('CREATE INDEX idx_ai_status_created ON ai_generations (status, created_at)');
        $this->addSql('CREATE TABLE articles (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , slug VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, content CLOB NOT NULL, excerpt CLOB DEFAULT NULL, featured_image_url VARCHAR(500) DEFAULT NULL, status VARCHAR(255) NOT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168989D9B62 ON articles (slug)');
        $this->addSql('CREATE INDEX idx_article_status_created ON articles (status, created_at)');
        $this->addSql('CREATE INDEX idx_article_slug ON articles (slug)');
        $this->addSql('CREATE TABLE article_categories (article_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , category_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(article_id, category_id), CONSTRAINT FK_62A97E97294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_62A97E912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_62A97E97294869C ON article_categories (article_id)');
        $this->addSql('CREATE INDEX IDX_62A97E912469DE2 ON article_categories (category_id)');
        $this->addSql('CREATE TABLE article_tags (article_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(article_id, tag_id), CONSTRAINT FK_DFFE13277294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DFFE1327BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DFFE13277294869C ON article_tags (article_id)');
        $this->addSql('CREATE INDEX IDX_DFFE1327BAD26311 ON article_tags (tag_id)');
        $this->addSql('CREATE TABLE categories (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF34668989D9B62 ON categories (slug)');
        $this->addSql('CREATE TABLE product_images (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , product_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , url VARCHAR(500) NOT NULL, format VARCHAR(50) DEFAULT NULL, dpi INTEGER DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, position INTEGER DEFAULT 0 NOT NULL, is_thumbnail BOOLEAN DEFAULT 0 NOT NULL, alt_text VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_8263FFCE4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8263FFCE4584665A ON product_images (product_id)');
        $this->addSql('CREATE TABLE products (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(255) NOT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A989D9B62 ON products (slug)');
        $this->addSql('CREATE INDEX idx_status_created ON products (status, created_at)');
        $this->addSql('CREATE INDEX idx_slug ON products (slug)');
        $this->addSql('CREATE TABLE product_categories (product_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , category_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(product_id, category_id), CONSTRAINT FK_A99419434584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A994194312469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A99419434584665A ON product_categories (product_id)');
        $this->addSql('CREATE INDEX IDX_A994194312469DE2 ON product_categories (category_id)');
        $this->addSql('CREATE TABLE product_tags (product_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(product_id, tag_id), CONSTRAINT FK_E254B6874584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E254B687BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E254B6874584665A ON product_tags (product_id)');
        $this->addSql('CREATE INDEX IDX_E254B687BAD26311 ON product_tags (tag_id)');
        $this->addSql('CREATE TABLE tags (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FBC94265E237E06 ON tags (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FBC9426989D9B62 ON tags (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ai_generations');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE article_categories');
        $this->addSql('DROP TABLE article_tags');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE product_images');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE product_categories');
        $this->addSql('DROP TABLE product_tags');
        $this->addSql('DROP TABLE tags');
    }
}
