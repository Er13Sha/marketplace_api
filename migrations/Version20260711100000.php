<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create seller profiles and make catalog products reference sellers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS sellers (id UUID NOT NULL, owner_user_id UUID NOT NULL, display_name VARCHAR(255) NOT NULL, legal_type VARCHAR(32) NOT NULL, tax_id VARCHAR(64) NOT NULL, phone_number VARCHAR(32) NOT NULL, address VARCHAR(500) NOT NULL, bank_name VARCHAR(255) DEFAULT NULL, bank_account VARCHAR(64) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_sellers_owner_user_id ON sellers (owner_user_id)');
        $this->addConstraintIfMissing('sellers', 'fk_sellers_owner_user', 'FOREIGN KEY (owner_user_id) REFERENCES auth_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('UPDATE catalog_products SET seller_id = NULL WHERE seller_id IS NOT NULL');
        $this->addSql('ALTER TABLE catalog_products DROP CONSTRAINT IF EXISTS fk_catalog_products_seller');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_catalog_products_seller_id ON catalog_products (seller_id)');
        $this->addConstraintIfMissing('catalog_products', 'fk_catalog_products_seller', 'FOREIGN KEY (seller_id) REFERENCES sellers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE catalog_products SET seller_id = NULL WHERE seller_id IS NOT NULL');
        $this->addSql('ALTER TABLE catalog_products DROP CONSTRAINT IF EXISTS fk_catalog_products_seller');
        $this->addConstraintIfMissing('catalog_products', 'fk_catalog_products_seller', 'FOREIGN KEY (seller_id) REFERENCES auth_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE IF EXISTS sellers');
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
