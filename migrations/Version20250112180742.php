<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250112180742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_templates (id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image_path LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, created_by INT NOT NULL, updated_by INT DEFAULT NULL, title VARCHAR(255) NOT NULL, `key` VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_5C93B3A45DA0FB8 (template_id), INDEX IDX_5C93B3A4DE12AB56 (created_by), INDEX IDX_5C93B3A416FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A45DA0FB8 FOREIGN KEY (template_id) REFERENCES project_templates (id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A416FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id)');

        $this->addSql('INSERT INTO project_templates (id, title, description, image_path) VALUES
            (1, "Kanban", "Visualize and advance your project forward using issues on a powerful board.", "kanban-planit.png"),
            (2, "Scrum", "Sprint toward your project goals with a board, backlog, and timeline.", "scrum-planit.png");
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A45DA0FB8');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4DE12AB56');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A416FE72E1');
        $this->addSql('DROP TABLE project_templates');
        $this->addSql('DROP TABLE projects');
    }
}
