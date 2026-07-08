<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708171732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move product stock from catalog to inventory tables.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS inventory_stock (product_id UUID NOT NULL, quantity INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(product_id))');

        $this->addSql('CREATE TABLE IF NOT EXISTS inventory_reservations (id UUID NOT NULL, product_id UUID NOT NULL, quantity INT NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, committed BOOLEAN DEFAULT false NOT NULL, released BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inventory_reservations_product_id ON inventory_reservations (product_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inventory_reservations_expires_at ON inventory_reservations (expires_at)');

        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_inventory_stock_product'
    ) THEN
        ALTER TABLE inventory_stock
            ADD CONSTRAINT fk_inventory_stock_product
            FOREIGN KEY (product_id)
            REFERENCES catalog_products (id)
            ON DELETE CASCADE
            NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END
$$;
SQL);

        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_inventory_reservations_product'
    ) THEN
        ALTER TABLE inventory_reservations
            ADD CONSTRAINT fk_inventory_reservations_product
            FOREIGN KEY (product_id)
            REFERENCES catalog_products (id)
            ON DELETE CASCADE
            NOT DEFERRABLE INITIALLY IMMEDIATE;
    END IF;
END
$$;
SQL);

        $this->addSql('INSERT INTO inventory_stock (product_id, quantity, updated_at) SELECT id, stock, updated_at FROM catalog_products ON CONFLICT (product_id) DO NOTHING');
        $this->addSql('ALTER TABLE catalog_products DROP COLUMN IF EXISTS stock');
    }

    public function postUp(Schema $schema): void
    {
        $this->importRedisStockIfAvailable();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Stock has been moved to inventory tables and cannot be safely restored to catalog_products.');
    }

    private function importRedisStockIfAvailable(): void
    {
        $redisUrl = getenv('REDIS_URL');
        if (!is_string($redisUrl) || $redisUrl === '' || !class_exists(\Predis\Client::class)) {
            return;
        }

        try {
            $redis = new \Predis\Client($redisUrl);
            $productIds = $this->connection->fetchFirstColumn('SELECT product_id FROM inventory_stock');
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

            foreach ($productIds as $productId) {
                $value = $redis->get('stock:' . (string) $productId);
                if ($value === null || !is_numeric($value)) {
                    continue;
                }

                $this->connection->update(
                    'inventory_stock',
                    [
                        'quantity' => (int) $value,
                        'updated_at' => $now,
                    ],
                    ['product_id' => (string) $productId]
                );
            }
        } catch (\Throwable) {
            // Catalog stock remains the fallback when Redis is not reachable during migration.
        }
    }
}
