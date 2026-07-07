<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\ValueObject\ProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\ReservationId;

class StockReservedEvent
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly Quantity $quantity,
        public readonly ReservationId $reservationId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
