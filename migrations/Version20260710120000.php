<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attach catalog products to seller accounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD COLUMN IF NOT EXISTS seller_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_catalog_products_seller_id ON catalog_products (seller_id)');
        $this->addConstraintIfMissing(
            'catalog_products',
            'fk_catalog_products_seller',
            'FOREIGN KEY (seller_id) REFERENCES auth_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP CONSTRAINT IF EXISTS fk_catalog_products_seller');
        $this->addSql('DROP INDEX IF EXISTS idx_catalog_products_seller_id');
        $this->addSql('ALTER TABLE catalog_products DROP COLUMN IF EXISTS seller_id');
    }

    private function addConstraintIfMissing(string $table, string $constraintName, string $constraintSql): void
    {
        $this->addSql(sprintf(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = '%s'
    ) THEN
        ALTER TABLE %s
            ADD CONSTRAINT %s
            %s;
    END IF;
END
$$;
SQL, $constraintName, $table, $constraintName, $constraintSql));
    }
}
