<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307105719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warning ADD user_id INT NOT NULL, ADD moderator_id INT NOT NULL, ADD reason VARCHAR(255) NOT NULL, ADD description LONGTEXT NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD viewed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE warning ADD CONSTRAINT FK_404E9CC6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE warning ADD CONSTRAINT FK_404E9CC6D0AFA354 FOREIGN KEY (moderator_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_404E9CC6A76ED395 ON warning (user_id)');
        $this->addSql('CREATE INDEX IDX_404E9CC6D0AFA354 ON warning (moderator_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warning DROP FOREIGN KEY FK_404E9CC6A76ED395');
        $this->addSql('ALTER TABLE warning DROP FOREIGN KEY FK_404E9CC6D0AFA354');
        $this->addSql('DROP INDEX IDX_404E9CC6A76ED395 ON warning');
        $this->addSql('DROP INDEX IDX_404E9CC6D0AFA354 ON warning');
        $this->addSql('ALTER TABLE warning DROP user_id, DROP moderator_id, DROP reason, DROP description, DROP created_at, DROP viewed');
    }
}
