<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\ValueObject\ProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\ReservationId;

class StockCommittedEvent
{
    public function __construct(
        public readonly ReservationId $reservationId,
        public readonly ProductId $productId,
        public readonly Quantity $quantity,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
