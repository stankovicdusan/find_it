<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250710165513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46BF700BD');
        $this->addSql('CREATE TABLE workflow_statuses (id INT AUTO_INCREMENT NOT NULL, workflow_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, sort_order INT NOT NULL, is_final TINYINT(1) NOT NULL, is_initial TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_8093E10B2C7C2CBA (workflow_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workflow_transitions (id INT AUTO_INCREMENT NOT NULL, from_status_id INT NOT NULL, to_status_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_C66989BB7B6B9507 (from_status_id), INDEX IDX_C66989BB5A54D7CC (to_status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workflows (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workflow_statuses ADD CONSTRAINT FK_8093E10B2C7C2CBA FOREIGN KEY (workflow_id) REFERENCES workflows (id)');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB7B6B9507 FOREIGN KEY (from_status_id) REFERENCES workflow_statuses (id)');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB5A54D7CC FOREIGN KEY (to_status_id) REFERENCES workflow_statuses (id)');
        $this->addSql('ALTER TABLE project_statuses DROP FOREIGN KEY FK_76BE95CA166D1F9C');
        $this->addSql('DROP TABLE project_statuses');
        $this->addSql('ALTER TABLE projects ADD workflow_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A42C7C2CBA FOREIGN KEY (workflow_id) REFERENCES workflows (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C93B3A42C7C2CBA ON projects (workflow_id)');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46BF700BD');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF46BF700BD FOREIGN KEY (status_id) REFERENCES workflow_statuses (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46BF700BD');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A42C7C2CBA');
        $this->addSql('CREATE TABLE project_statuses (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, `order` INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_76BE95CA166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_statuses ADD CONSTRAINT FK_76BE95CA166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE workflow_statuses DROP FOREIGN KEY FK_8093E10B2C7C2CBA');
        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB7B6B9507');
        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB5A54D7CC');
        $this->addSql('DROP TABLE workflow_statuses');
        $this->addSql('DROP TABLE workflow_transitions');
        $this->addSql('DROP TABLE workflows');
        $this->addSql('DROP INDEX UNIQ_5C93B3A42C7C2CBA ON projects');
        $this->addSql('ALTER TABLE projects DROP workflow_id');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46BF700BD');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF46BF700BD FOREIGN KEY (status_id) REFERENCES project_statuses (id)');
    }
}
