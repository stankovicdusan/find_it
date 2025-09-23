<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923155035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_comments ADD created_by INT NOT NULL, ADD updated_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_comments ADD CONSTRAINT FK_DAF76AABDE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ticket_comments ADD CONSTRAINT FK_DAF76AAB16FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_DAF76AABDE12AB56 ON ticket_comments (created_by)');
        $this->addSql('CREATE INDEX IDX_DAF76AAB16FE72E1 ON ticket_comments (updated_by)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_comments DROP FOREIGN KEY FK_DAF76AABDE12AB56');
        $this->addSql('ALTER TABLE ticket_comments DROP FOREIGN KEY FK_DAF76AAB16FE72E1');
        $this->addSql('DROP INDEX IDX_DAF76AABDE12AB56 ON ticket_comments');
        $this->addSql('DROP INDEX IDX_DAF76AAB16FE72E1 ON ticket_comments');
        $this->addSql('ALTER TABLE ticket_comments DROP created_by, DROP updated_by');
    }
}
