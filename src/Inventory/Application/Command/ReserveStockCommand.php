<?php
declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;

class ReserveStockCommand
{
    public function __construct(
        public readonly CatalogProductId $productId,
        public readonly Quantity $quantity,
        public readonly int $ttlSeconds = 900 // 15 минут по умолчанию
    ) {}
}
