<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923170421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_members (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(190) NOT NULL, role VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_D3BEDE9A166D1F9C (project_id), INDEX IDX_D3BEDE9AA76ED395 (user_id), UNIQUE INDEX UNIQ_D3BEDE9A166D1F9CE7927C74 (project_id, email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_members ADD CONSTRAINT FK_D3BEDE9A166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_members ADD CONSTRAINT FK_D3BEDE9AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_members DROP FOREIGN KEY FK_D3BEDE9A166D1F9C');
        $this->addSql('ALTER TABLE project_members DROP FOREIGN KEY FK_D3BEDE9AA76ED395');
        $this->addSql('DROP TABLE project_members');
    }
}
