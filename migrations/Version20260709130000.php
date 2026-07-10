<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders and order items tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS orders (id UUID NOT NULL, user_id UUID NOT NULL, cart_id UUID DEFAULT NULL, status VARCHAR(32) NOT NULL, total INT NOT NULL, currency VARCHAR(3) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders (user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_orders_cart_id ON orders (cart_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status)');

        $this->addSql('CREATE TABLE IF NOT EXISTS order_items (id UUID NOT NULL, order_id UUID NOT NULL, product_id UUID NOT NULL, product_sku VARCHAR(64) NOT NULL, product_name VARCHAR(255) NOT NULL, price_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, quantity INT NOT NULL, line_total INT NOT NULL, reservation_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items (order_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items (product_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_order_items_reservation_id ON order_items (reservation_id)');

        $this->addConstraintIfMissing('orders', 'fk_orders_user', 'FOREIGN KEY (user_id) REFERENCES auth_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('orders', 'fk_orders_cart', 'FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('order_items', 'fk_order_items_order', 'FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addConstraintIfMissing('order_items', 'chk_order_items_quantity_positive', 'CHECK (quantity > 0)');
        $this->addConstraintIfMissing('order_items', 'chk_order_items_price_non_negative', 'CHECK (price_amount >= 0)');
        $this->addConstraintIfMissing('order_items', 'chk_order_items_line_total_non_negative', 'CHECK (line_total >= 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_items');
        $this->addSql('DROP TABLE IF EXISTS orders');
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
