<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926230321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE roles (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_users ADD role_id INT NOT NULL, DROP role');
        $this->addSql('ALTER TABLE project_users ADD CONSTRAINT FK_7D6AC77D60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('CREATE INDEX IDX_7D6AC77D60322AC ON project_users (role_id)');

        $this->addSql('INSERT INTO roles (id, name) VALUES
            (1, "Admin"),
            (2, "Manager"),
            (3, "Employee");
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_users DROP FOREIGN KEY FK_7D6AC77D60322AC');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP INDEX IDX_7D6AC77D60322AC ON project_users');
        $this->addSql('ALTER TABLE project_users ADD role VARCHAR(20) NOT NULL, DROP role_id');
    }
}
