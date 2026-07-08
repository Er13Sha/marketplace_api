<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Repository;

use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\ReservationId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class ReservationDoctrineRepository implements ReservationRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    public function save(Reservation $reservation): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO inventory_reservations (
    id,
    product_id,
    quantity,
    expires_at,
    committed,
    released,
    created_at,
    updated_at
)
VALUES (
    :id,
    :productId,
    :quantity,
    :expiresAt,
    :committed,
    :released,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
)
ON CONFLICT (id)
DO UPDATE SET product_id = EXCLUDED.product_id,
              quantity = EXCLUDED.quantity,
              expires_at = EXCLUDED.expires_at,
              committed = EXCLUDED.committed,
              released = EXCLUDED.released,
              updated_at = CURRENT_TIMESTAMP
SQL,
            [
                'id' => $reservation->getId()->toString(),
                'productId' => $reservation->getProductId()->toString(),
                'quantity' => $reservation->getQuantity()->getValue(),
                'expiresAt' => $reservation->getExpiresAt()->format('Y-m-d H:i:s'),
                'committed' => $reservation->isCommitted(),
                'released' => $reservation->isReleased(),
            ],
            [
                'quantity' => ParameterType::INTEGER,
                'committed' => ParameterType::BOOLEAN,
                'released' => ParameterType::BOOLEAN,
            ]
        );
    }

    public function findById(ReservationId $id): ?Reservation
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
SELECT id, product_id, quantity, expires_at, committed, released
FROM inventory_reservations
WHERE id = :id
FOR UPDATE
SQL,
            ['id' => $id->toString()]
        );

        if ($row === false) {
            return null;
        }

        return Reservation::restore(
            ReservationId::fromString((string) $row['id']),
            CatalogProductId::fromString((string) $row['product_id']),
            new Quantity((int) $row['quantity']),
            new \DateTimeImmutable((string) $row['expires_at']),
            $this->toBool($row['committed']),
            $this->toBool($row['released'])
        );
    }

    public function delete(ReservationId $id): void
    {
        $this->connection->delete('inventory_reservations', ['id' => $id->toString()]);
    }

    private function toBool(mixed $value): bool
    {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 't'
            || $value === 'true';
    }
}
