<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Event;

use App\Catalog\Domain\ValueObject\ProductId;

class ProductUpdatedEvent
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
