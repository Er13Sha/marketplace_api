<?php
declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;

final class IncreaseStockCommand
{
    public function __construct(
        public readonly CatalogProductId $productId,
        public readonly Quantity $quantity
    ) {}
}
