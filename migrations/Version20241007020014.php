<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007020014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (
          id BIGINT AUTO_INCREMENT NOT NULL,
          type_id BIGINT NOT NULL,
          link_id BIGINT DEFAULT NULL,
          parent_id BIGINT DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          INDEX IDX_64C19C1C54C8C93 (type_id),
          INDEX IDX_64C19C1ADA40271 (link_id),
          INDEX IDX_64C19C1727ACA70 (parent_id),
          UNIQUE INDEX UNIQ_64C19C1C54C8C9377153098 (type_id, code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_type (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          UNIQUE INDEX UNIQ_7452D6E77153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(12) NOT NULL COLLATE `utf8mb4_bin`,
          published_from DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          published_to DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          total_chapter INT DEFAULT NULL,
          total_volume INT DEFAULT NULL,
          nsfw SMALLINT DEFAULT NULL,
          nsfl SMALLINT DEFAULT NULL,
          UNIQUE INDEX UNIQ_5B7EA5AA77153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_author (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          type_id BIGINT NOT NULL,
          person_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_B478E53BD663094A (comic_id),
          INDEX IDX_B478E53BC54C8C93 (type_id),
          INDEX IDX_B478E53B217BBB47 (person_id),
          UNIQUE INDEX UNIQ_B478E53BD663094AC54C8C93217BBB47 (comic_id, type_id, person_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_author_note (
          id BIGINT AUTO_INCREMENT NOT NULL,
          author_id BIGINT NOT NULL,
          language_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          content VARCHAR(255) NOT NULL,
          INDEX IDX_7F83E0C6F675F31B (author_id),
          INDEX IDX_7F83E0C682F1BAF4 (language_id),
          UNIQUE INDEX UNIQ_7F83E0C6F675F31BC288C859 (author_id, ulid),
          UNIQUE INDEX UNIQ_7F83E0C6F675F31B82F1BAF4 (author_id, language_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_author_type (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          UNIQUE INDEX UNIQ_3CE04DFB77153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_category (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          category_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_61B5EE79D663094A (comic_id),
          INDEX IDX_61B5EE7912469DE2 (category_id),
          UNIQUE INDEX UNIQ_61B5EE79D663094A12469DE2 (comic_id, category_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_chapter (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          thumbnail_link_id BIGINT DEFAULT NULL,
          volume_id BIGINT DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          number NUMERIC(10, 2) NOT NULL,
          version VARCHAR(64) DEFAULT \'\' NOT NULL,
          released_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_DD3CC1B5D663094A (comic_id),
          INDEX IDX_DD3CC1B58F006908 (thumbnail_link_id),
          INDEX IDX_DD3CC1B58FD80EEA (volume_id),
          UNIQUE INDEX UNIQ_DD3CC1B5D663094A96901F54BF1CD3C3 (comic_id, number, version),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_chapter_title (
          id BIGINT AUTO_INCREMENT NOT NULL,
          chapter_id BIGINT NOT NULL,
          language_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          content VARCHAR(255) NOT NULL COLLATE `utf8mb4_bin`,
          is_synonym TINYINT(1) DEFAULT NULL,
          is_latinized TINYINT(1) DEFAULT NULL,
          INDEX IDX_D03620579F4768 (chapter_id),
          INDEX IDX_D0362082F1BAF4 (language_id),
          UNIQUE INDEX UNIQ_D03620579F4768C288C859 (chapter_id, ulid),
          UNIQUE INDEX UNIQ_D03620579F4768FEC530A9 (chapter_id, content),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_character (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          character_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          is_main TINYINT(1) DEFAULT NULL,
          INDEX IDX_56A7727DD663094A (comic_id),
          INDEX IDX_56A7727D1136BE75 (character_id),
          UNIQUE INDEX UNIQ_56A7727DD663094A1136BE75 (comic_id, character_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_cover (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          link_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          hint VARCHAR(64) DEFAULT NULL,
          INDEX IDX_EC795EC9D663094A (comic_id),
          INDEX IDX_EC795EC9ADA40271 (link_id),
          UNIQUE INDEX UNIQ_EC795EC9D663094AC288C859 (comic_id, ulid),
          UNIQUE INDEX UNIQ_EC795EC9D663094AADA40271 (comic_id, link_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_external (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          link_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          is_official TINYINT(1) DEFAULT NULL,
          is_community TINYINT(1) DEFAULT NULL,
          INDEX IDX_3FAB5600D663094A (comic_id),
          INDEX IDX_3FAB5600ADA40271 (link_id),
          UNIQUE INDEX UNIQ_3FAB5600D663094AC288C859 (comic_id, ulid),
          UNIQUE INDEX UNIQ_3FAB5600D663094AADA40271 (comic_id, link_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_relation (
          id BIGINT AUTO_INCREMENT NOT NULL,
          parent_id BIGINT NOT NULL,
          type_id BIGINT NOT NULL,
          child_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_570B0F1727ACA70 (parent_id),
          INDEX IDX_570B0F1C54C8C93 (type_id),
          INDEX IDX_570B0F1DD62C21B (child_id),
          UNIQUE INDEX UNIQ_570B0F1727ACA70C54C8C93DD62C21B (parent_id, type_id, child_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_relation_type (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          UNIQUE INDEX UNIQ_905B8C5077153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_serialization (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          magazine_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_AF05F480D663094A (comic_id),
          INDEX IDX_AF05F4803EB84A1D (magazine_id),
          UNIQUE INDEX UNIQ_AF05F480D663094A3EB84A1D (comic_id, magazine_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_synopsis (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          language_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          content VARCHAR(2040) NOT NULL,
          source VARCHAR(32) DEFAULT \'\' NOT NULL,
          INDEX IDX_30D32311D663094A (comic_id),
          INDEX IDX_30D3231182F1BAF4 (language_id),
          UNIQUE INDEX UNIQ_30D32311D663094AC288C859 (comic_id, ulid),
          UNIQUE INDEX UNIQ_30D32311D663094A82F1BAF45F8A7F73 (comic_id, language_id, source),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_tag (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          tag_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_FE821497D663094A (comic_id),
          INDEX IDX_FE821497BAD26311 (tag_id),
          UNIQUE INDEX UNIQ_FE821497D663094ABAD26311 (comic_id, tag_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_title (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          language_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          content VARCHAR(255) NOT NULL COLLATE `utf8mb4_bin`,
          is_synonym TINYINT(1) DEFAULT NULL,
          is_latinized TINYINT(1) DEFAULT NULL,
          INDEX IDX_4A47A067D663094A (comic_id),
          INDEX IDX_4A47A06782F1BAF4 (language_id),
          UNIQUE INDEX UNIQ_4A47A067D663094AC288C859 (comic_id, ulid),
          UNIQUE INDEX UNIQ_4A47A067D663094AFEC530A9 (comic_id, content),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_volume (
          id BIGINT AUTO_INCREMENT NOT NULL,
          comic_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          number NUMERIC(10, 2) NOT NULL,
          released_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          INDEX IDX_B04DF02DD663094A (comic_id),
          UNIQUE INDEX UNIQ_B04DF02DD663094A96901F54 (comic_id, number),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_volume_cover (
          id BIGINT AUTO_INCREMENT NOT NULL,
          volume_id BIGINT NOT NULL,
          link_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          hint VARCHAR(64) DEFAULT NULL,
          INDEX IDX_5816521C8FD80EEA (volume_id),
          INDEX IDX_5816521CADA40271 (link_id),
          UNIQUE INDEX UNIQ_5816521C8FD80EEAC288C859 (volume_id, ulid),
          UNIQUE INDEX UNIQ_5816521C8FD80EEAADA40271 (volume_id, link_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comic_volume_title (
          id BIGINT AUTO_INCREMENT NOT NULL,
          volume_id BIGINT NOT NULL,
          language_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          ulid BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\',
          content VARCHAR(255) NOT NULL COLLATE `utf8mb4_bin`,
          is_synonym TINYINT(1) DEFAULT NULL,
          is_latinized TINYINT(1) DEFAULT NULL,
          INDEX IDX_FE28ACB28FD80EEA (volume_id),
          INDEX IDX_FE28ACB282F1BAF4 (language_id),
          UNIQUE INDEX UNIQ_FE28ACB28FD80EEAC288C859 (volume_id, ulid),
          UNIQUE INDEX UNIQ_FE28ACB28FD80EEAFEC530A9 (volume_id, content),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE language (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          lang VARCHAR(16) NOT NULL,
          name VARCHAR(32) NOT NULL,
          UNIQUE INDEX UNIQ_D4DB71B531098462 (lang),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE link (
          id BIGINT AUTO_INCREMENT NOT NULL,
          website_id BIGINT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          relative_reference VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE `utf8mb4_bin`,
          INDEX IDX_36AC99F118F45C82 (website_id),
          UNIQUE INDEX UNIQ_36AC99F118F45C82D9AE39BE (website_id, relative_reference),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE magazine (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(12) NOT NULL COLLATE `utf8mb4_bin`,
          UNIQUE INDEX UNIQ_378C2FE477153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(12) NOT NULL COLLATE `utf8mb4_bin`,
          UNIQUE INDEX UNIQ_34DCD17677153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (
          id BIGINT AUTO_INCREMENT NOT NULL,
          type_id BIGINT NOT NULL,
          link_id BIGINT DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          INDEX IDX_389B783C54C8C93 (type_id),
          INDEX IDX_389B783ADA40271 (link_id),
          UNIQUE INDEX UNIQ_389B783C54C8C9377153098 (type_id, code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_type (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(32) NOT NULL,
          name VARCHAR(32) NOT NULL,
          UNIQUE INDEX UNIQ_62D1E89F77153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE website (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          host VARCHAR(64) NOT NULL,
          name VARCHAR(64) NOT NULL,
          UNIQUE INDEX UNIQ_476F5DE7CF2713FD (host),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE xcharacter (
          id BIGINT AUTO_INCREMENT NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\',
          code VARCHAR(12) NOT NULL COLLATE `utf8mb4_bin`,
          UNIQUE INDEX UNIQ_7367098077153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          category
        ADD
          CONSTRAINT FK_64C19C1C54C8C93 FOREIGN KEY (type_id) REFERENCES category_type (id)');
        $this->addSql('ALTER TABLE
          category
        ADD
          CONSTRAINT FK_64C19C1ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE
        SET
          NULL');
        $this->addSql('ALTER TABLE
          category
        ADD
          CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_author
        ADD
          CONSTRAINT FK_B478E53BD663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_author
        ADD
          CONSTRAINT FK_B478E53BC54C8C93 FOREIGN KEY (type_id) REFERENCES comic_author_type (id)');
        $this->addSql('ALTER TABLE
          comic_author
        ADD
          CONSTRAINT FK_B478E53B217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_author_note
        ADD
          CONSTRAINT FK_7F83E0C6F675F31B FOREIGN KEY (author_id) REFERENCES comic_author (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_author_note
        ADD
          CONSTRAINT FK_7F83E0C682F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE
          comic_category
        ADD
          CONSTRAINT FK_61B5EE79D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_category
        ADD
          CONSTRAINT FK_61B5EE7912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_chapter
        ADD
          CONSTRAINT FK_DD3CC1B5D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_chapter
        ADD
          CONSTRAINT FK_DD3CC1B58F006908 FOREIGN KEY (thumbnail_link_id) REFERENCES link (id)');
        $this->addSql('ALTER TABLE
          comic_chapter
        ADD
          CONSTRAINT FK_DD3CC1B58FD80EEA FOREIGN KEY (volume_id) REFERENCES comic_volume (id)');
        $this->addSql('ALTER TABLE
          comic_chapter_title
        ADD
          CONSTRAINT FK_D03620579F4768 FOREIGN KEY (chapter_id) REFERENCES comic_chapter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_chapter_title
        ADD
          CONSTRAINT FK_D0362082F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE
          comic_character
        ADD
          CONSTRAINT FK_56A7727DD663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_character
        ADD
          CONSTRAINT FK_56A7727D1136BE75 FOREIGN KEY (character_id) REFERENCES xcharacter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_cover
        ADD
          CONSTRAINT FK_EC795EC9D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_cover
        ADD
          CONSTRAINT FK_EC795EC9ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_external
        ADD
          CONSTRAINT FK_3FAB5600D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_external
        ADD
          CONSTRAINT FK_3FAB5600ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_relation
        ADD
          CONSTRAINT FK_570B0F1727ACA70 FOREIGN KEY (parent_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_relation
        ADD
          CONSTRAINT FK_570B0F1C54C8C93 FOREIGN KEY (type_id) REFERENCES comic_relation_type (id)');
        $this->addSql('ALTER TABLE
          comic_relation
        ADD
          CONSTRAINT FK_570B0F1DD62C21B FOREIGN KEY (child_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_serialization
        ADD
          CONSTRAINT FK_AF05F480D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_serialization
        ADD
          CONSTRAINT FK_AF05F4803EB84A1D FOREIGN KEY (magazine_id) REFERENCES magazine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_synopsis
        ADD
          CONSTRAINT FK_30D32311D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_synopsis
        ADD
          CONSTRAINT FK_30D3231182F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE
          comic_tag
        ADD
          CONSTRAINT FK_FE821497D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_tag
        ADD
          CONSTRAINT FK_FE821497BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_title
        ADD
          CONSTRAINT FK_4A47A067D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_title
        ADD
          CONSTRAINT FK_4A47A06782F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE
          comic_volume
        ADD
          CONSTRAINT FK_B04DF02DD663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_volume_cover
        ADD
          CONSTRAINT FK_5816521C8FD80EEA FOREIGN KEY (volume_id) REFERENCES comic_volume (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_volume_cover
        ADD
          CONSTRAINT FK_5816521CADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_volume_title
        ADD
          CONSTRAINT FK_FE28ACB28FD80EEA FOREIGN KEY (volume_id) REFERENCES comic_volume (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          comic_volume_title
        ADD
          CONSTRAINT FK_FE28ACB282F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE
          link
        ADD
          CONSTRAINT FK_36AC99F118F45C82 FOREIGN KEY (website_id) REFERENCES website (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          tag
        ADD
          CONSTRAINT FK_389B783C54C8C93 FOREIGN KEY (type_id) REFERENCES tag_type (id)');
        $this->addSql('ALTER TABLE
          tag
        ADD
          CONSTRAINT FK_389B783ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE
        SET
          NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1C54C8C93');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1ADA40271');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE comic_author DROP FOREIGN KEY FK_B478E53BD663094A');
        $this->addSql('ALTER TABLE comic_author DROP FOREIGN KEY FK_B478E53BC54C8C93');
        $this->addSql('ALTER TABLE comic_author DROP FOREIGN KEY FK_B478E53B217BBB47');
        $this->addSql('ALTER TABLE comic_author_note DROP FOREIGN KEY FK_7F83E0C6F675F31B');
        $this->addSql('ALTER TABLE comic_author_note DROP FOREIGN KEY FK_7F83E0C682F1BAF4');
        $this->addSql('ALTER TABLE comic_category DROP FOREIGN KEY FK_61B5EE79D663094A');
        $this->addSql('ALTER TABLE comic_category DROP FOREIGN KEY FK_61B5EE7912469DE2');
        $this->addSql('ALTER TABLE comic_chapter DROP FOREIGN KEY FK_DD3CC1B5D663094A');
        $this->addSql('ALTER TABLE comic_chapter DROP FOREIGN KEY FK_DD3CC1B58F006908');
        $this->addSql('ALTER TABLE comic_chapter DROP FOREIGN KEY FK_DD3CC1B58FD80EEA');
        $this->addSql('ALTER TABLE comic_chapter_title DROP FOREIGN KEY FK_D03620579F4768');
        $this->addSql('ALTER TABLE comic_chapter_title DROP FOREIGN KEY FK_D0362082F1BAF4');
        $this->addSql('ALTER TABLE comic_character DROP FOREIGN KEY FK_56A7727DD663094A');
        $this->addSql('ALTER TABLE comic_character DROP FOREIGN KEY FK_56A7727D1136BE75');
        $this->addSql('ALTER TABLE comic_cover DROP FOREIGN KEY FK_EC795EC9D663094A');
        $this->addSql('ALTER TABLE comic_cover DROP FOREIGN KEY FK_EC795EC9ADA40271');
        $this->addSql('ALTER TABLE comic_external DROP FOREIGN KEY FK_3FAB5600D663094A');
        $this->addSql('ALTER TABLE comic_external DROP FOREIGN KEY FK_3FAB5600ADA40271');
        $this->addSql('ALTER TABLE comic_relation DROP FOREIGN KEY FK_570B0F1727ACA70');
        $this->addSql('ALTER TABLE comic_relation DROP FOREIGN KEY FK_570B0F1C54C8C93');
        $this->addSql('ALTER TABLE comic_relation DROP FOREIGN KEY FK_570B0F1DD62C21B');
        $this->addSql('ALTER TABLE comic_serialization DROP FOREIGN KEY FK_AF05F480D663094A');
        $this->addSql('ALTER TABLE comic_serialization DROP FOREIGN KEY FK_AF05F4803EB84A1D');
        $this->addSql('ALTER TABLE comic_synopsis DROP FOREIGN KEY FK_30D32311D663094A');
        $this->addSql('ALTER TABLE comic_synopsis DROP FOREIGN KEY FK_30D3231182F1BAF4');
        $this->addSql('ALTER TABLE comic_tag DROP FOREIGN KEY FK_FE821497D663094A');
        $this->addSql('ALTER TABLE comic_tag DROP FOREIGN KEY FK_FE821497BAD26311');
        $this->addSql('ALTER TABLE comic_title DROP FOREIGN KEY FK_4A47A067D663094A');
        $this->addSql('ALTER TABLE comic_title DROP FOREIGN KEY FK_4A47A06782F1BAF4');
        $this->addSql('ALTER TABLE comic_volume DROP FOREIGN KEY FK_B04DF02DD663094A');
        $this->addSql('ALTER TABLE comic_volume_cover DROP FOREIGN KEY FK_5816521C8FD80EEA');
        $this->addSql('ALTER TABLE comic_volume_cover DROP FOREIGN KEY FK_5816521CADA40271');
        $this->addSql('ALTER TABLE comic_volume_title DROP FOREIGN KEY FK_FE28ACB28FD80EEA');
        $this->addSql('ALTER TABLE comic_volume_title DROP FOREIGN KEY FK_FE28ACB282F1BAF4');
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F118F45C82');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783C54C8C93');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783ADA40271');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_type');
        $this->addSql('DROP TABLE comic');
        $this->addSql('DROP TABLE comic_author');
        $this->addSql('DROP TABLE comic_author_note');
        $this->addSql('DROP TABLE comic_author_type');
        $this->addSql('DROP TABLE comic_category');
        $this->addSql('DROP TABLE comic_chapter');
        $this->addSql('DROP TABLE comic_chapter_title');
        $this->addSql('DROP TABLE comic_character');
        $this->addSql('DROP TABLE comic_cover');
        $this->addSql('DROP TABLE comic_external');
        $this->addSql('DROP TABLE comic_relation');
        $this->addSql('DROP TABLE comic_relation_type');
        $this->addSql('DROP TABLE comic_serialization');
        $this->addSql('DROP TABLE comic_synopsis');
        $this->addSql('DROP TABLE comic_tag');
        $this->addSql('DROP TABLE comic_title');
        $this->addSql('DROP TABLE comic_volume');
        $this->addSql('DROP TABLE comic_volume_cover');
        $this->addSql('DROP TABLE comic_volume_title');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE link');
        $this->addSql('DROP TABLE magazine');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tag_type');
        $this->addSql('DROP TABLE website');
        $this->addSql('DROP TABLE xcharacter');
    }
}
