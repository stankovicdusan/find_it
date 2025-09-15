<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250710123402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets ADD issue_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF460B4C972 FOREIGN KEY (issue_type_id) REFERENCES issue_types (id)');
        $this->addSql('CREATE INDEX IDX_54469DF460B4C972 ON tickets (issue_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF460B4C972');
        $this->addSql('DROP INDEX IDX_54469DF460B4C972 ON tickets');
        $this->addSql('ALTER TABLE tickets DROP issue_type_id');
    }
}
