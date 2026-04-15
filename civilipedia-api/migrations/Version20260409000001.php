<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne `image` (nullable) à la table `article`.
 * Exécuter avec : php bin/console doctrine:migrations:migrate
 */
final class Version20260409000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ image (nullable) à la table article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE article ADD COLUMN image VARCHAR(255) NULL DEFAULT NULL"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE article DROP COLUMN image"
        );
    }
}
