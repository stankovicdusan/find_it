<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128181856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE issue_types (id INT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_statuses (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, title VARCHAR(255) NOT NULL, `order` INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_76BE95CA166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tickets (id INT AUTO_INCREMENT NOT NULL, ADD status_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, created_by INT NOT NULL, updated_by INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, index_number INT NOT NULL, priority VARCHAR(255) NOT NULL, `order` INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_54469DF46BF700BD (status_id), INDEX IDX_54469DF4F4BD7827 (assigned_to_id), INDEX IDX_54469DF4DE12AB56 (created_by), INDEX IDX_54469DF416FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_statuses ADD CONSTRAINT FK_76BE95CA166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF46BF700BD FOREIGN KEY (status_id) REFERENCES project_statuses (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF416FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id)');

        $this->addSql(
            'INSERT INTO issue_types (id, title) VALUES
                (1, "Bug"),
                (2, "Task");
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_statuses DROP FOREIGN KEY FK_76BE95CA166D1F9C');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4166D1F9C');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4F4BD7827');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4DE12AB56');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF416FE72E1');
        $this->addSql('DROP TABLE issue_types');
        $this->addSql('DROP TABLE project_statuses');
        $this->addSql('DROP TABLE tickets');
    }
}
