<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924200903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sprints (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(120) NOT NULL, goal LONGTEXT DEFAULT NULL, planned_start_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', planned_end_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', state VARCHAR(20) NOT NULL, INDEX IDX_4EE46971166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sprints ADD CONSTRAINT FK_4EE46971166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tickets ADD sprint_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48C24077B FOREIGN KEY (sprint_id) REFERENCES sprints (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_54469DF48C24077B ON tickets (sprint_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF48C24077B');
        $this->addSql('ALTER TABLE sprints DROP FOREIGN KEY FK_4EE46971166D1F9C');
        $this->addSql('DROP TABLE sprints');
        $this->addSql('DROP INDEX IDX_54469DF48C24077B ON tickets');
        $this->addSql('ALTER TABLE tickets DROP sprint_id');
    }
}
