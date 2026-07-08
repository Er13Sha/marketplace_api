<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\ValueObject\ReservationId;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;

class Reservation
{
    private ReservationId $id;
    private CatalogProductId $productId;
    private Quantity $quantity;
    private \DateTimeImmutable $expiresAt;
    private bool $committed = false;
    private bool $released = false;

    public function __construct(CatalogProductId $productId, Quantity $quantity, \DateInterval $ttl)
    {
        $this->id = new ReservationId();
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->expiresAt = (new \DateTimeImmutable())->add($ttl);
    }

    public static function restore(
        ReservationId $id,
        CatalogProductId $productId,
        Quantity $quantity,
        \DateTimeImmutable $expiresAt,
        bool $committed,
        bool $released
    ): self {
        $reservation = new self($productId, $quantity, new \DateInterval('PT0S'));
        $reservation->id = $id;
        $reservation->expiresAt = $expiresAt;
        $reservation->committed = $committed;
        $reservation->released = $released;

        return $reservation;
    }

    public function getId(): ReservationId { return $this->id; }
    public function getProductId(): CatalogProductId { return $this->productId; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isExpired(): bool { return new \DateTimeImmutable() > $this->expiresAt; }
    public function isCommitted(): bool { return $this->committed; }
    public function isReleased(): bool { return $this->released; }

    public function commit(): void
    {
        if ($this->committed || $this->released) {
            throw new \DomainException('Reservation already finalized');
        }
        $this->committed = true;
    }

    public function release(): void
    {
        if ($this->committed || $this->released) {
            throw new \DomainException('Reservation already finalized');
        }
        $this->released = true;
    }
}
