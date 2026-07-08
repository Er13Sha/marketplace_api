<?php
declare(strict_types=1);


namespace App\Catalog\Domain\Event;

use App\Catalog\Domain\ValueObject\ProductId;

class ProductCreatedEvent
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly int $initialStock,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
