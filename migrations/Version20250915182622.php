<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915182622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4166D1F9C');
        $this->addSql('DROP INDEX IDX_54469DF4166D1F9C ON tickets');
        $this->addSql('ALTER TABLE tickets CHANGE index_number index_number INT NOT NULL, CHANGE project_id status_id INT NOT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF46BF700BD FOREIGN KEY (status_id) REFERENCES workflow_statuses (id)');
        $this->addSql('CREATE INDEX IDX_54469DF46BF700BD ON tickets (status_id)');

        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB5A54D7CC');
        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB7B6B9507');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB5A54D7CC FOREIGN KEY (to_status_id) REFERENCES workflow_statuses (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB7B6B9507 FOREIGN KEY (from_status_id) REFERENCES workflow_statuses (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB7B6B9507');
        $this->addSql('ALTER TABLE workflow_transitions DROP FOREIGN KEY FK_C66989BB5A54D7CC');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB7B6B9507 FOREIGN KEY (from_status_id) REFERENCES workflow_statuses (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE workflow_transitions ADD CONSTRAINT FK_C66989BB5A54D7CC FOREIGN KEY (to_status_id) REFERENCES workflow_statuses (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46BF700BD');
        $this->addSql('DROP INDEX IDX_54469DF46BF700BD ON tickets');
        $this->addSql('ALTER TABLE tickets CHANGE index_number index_number VARCHAR(255) NOT NULL, CHANGE status_id project_id INT NOT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_54469DF4166D1F9C ON tickets (project_id)');
    }
}
