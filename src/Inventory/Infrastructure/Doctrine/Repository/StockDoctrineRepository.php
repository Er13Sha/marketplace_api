<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Repository;

use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class StockDoctrineRepository implements StockRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    public function get(CatalogProductId $productId): ?Stock
    {
        $row = $this->connection->fetchAssociative(
            'SELECT product_id, quantity, updated_at FROM inventory_stock WHERE product_id = :productId',
            ['productId' => $productId->toString()]
        );

        if ($row === false) {
            return null;
        }

        return Stock::restore(
            CatalogProductId::fromString((string) $row['product_id']),
            new Quantity((int) $row['quantity']),
            new \DateTimeImmutable((string) $row['updated_at'])
        );
    }

    public function save(Stock $stock): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO inventory_stock (product_id, quantity, updated_at)
VALUES (:productId, :quantity, :updatedAt)
ON CONFLICT (product_id)
DO UPDATE SET quantity = EXCLUDED.quantity, updated_at = EXCLUDED.updated_at
SQL,
            [
                'productId' => $stock->getProductId()->toString(),
                'quantity' => $stock->getQuantity()->getValue(),
                'updatedAt' => $stock->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
            [
                'quantity' => ParameterType::INTEGER,
            ]
        );
    }

    public function decrease(CatalogProductId $productId, Quantity $quantity): void
    {
        $affectedRows = $this->connection->executeStatement(
            <<<'SQL'
UPDATE inventory_stock
SET quantity = quantity - :quantity,
    updated_at = CURRENT_TIMESTAMP
WHERE product_id = :productId
  AND quantity >= :quantity
SQL,
            [
                'productId' => $productId->toString(),
                'quantity' => $quantity->getValue(),
            ],
            [
                'quantity' => ParameterType::INTEGER,
            ]
        );

        if ($affectedRows < 1) {
            throw new \DomainException('Insufficient stock');
        }
    }

    public function increase(CatalogProductId $productId, Quantity $quantity): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO inventory_stock (product_id, quantity, updated_at)
VALUES (:productId, :quantity, CURRENT_TIMESTAMP)
ON CONFLICT (product_id)
DO UPDATE SET quantity = inventory_stock.quantity + EXCLUDED.quantity,
              updated_at = CURRENT_TIMESTAMP
SQL,
            [
                'productId' => $productId->toString(),
                'quantity' => $quantity->getValue(),
            ],
            [
                'quantity' => ParameterType::INTEGER,
            ]
        );
    }

    public function initialize(CatalogProductId $productId, Quantity $initialQuantity): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO inventory_stock (product_id, quantity, updated_at)
VALUES (:productId, :quantity, CURRENT_TIMESTAMP)
ON CONFLICT (product_id) DO NOTHING
SQL,
            [
                'productId' => $productId->toString(),
                'quantity' => $initialQuantity->getValue(),
            ],
            [
                'quantity' => ParameterType::INTEGER,
            ]
        );
    }
}
