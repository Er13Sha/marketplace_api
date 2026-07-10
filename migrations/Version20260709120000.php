<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create carts and cart items tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS carts (id UUID NOT NULL, user_id UUID NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_carts_user_id ON carts (user_id)');
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_carts_active_user ON carts (user_id) WHERE status = 'active'");

        $this->addSql('CREATE TABLE IF NOT EXISTS cart_items (id UUID NOT NULL, cart_id UUID NOT NULL, product_id UUID NOT NULL, quantity INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cart_items_cart_id ON cart_items (cart_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cart_items_product_id ON cart_items (product_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_cart_items_cart_product ON cart_items (cart_id, product_id)');

        $this->addConstraintIfMissing('carts', 'fk_carts_user', 'FOREIGN KEY (user_id) REFERENCES auth_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('cart_items', 'fk_cart_items_cart', 'FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('cart_items', 'fk_cart_items_product', 'FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('cart_items', 'chk_cart_items_quantity_positive', 'CHECK (quantity > 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cart_items');
        $this->addSql('DROP TABLE IF EXISTS carts');
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
