<?php
declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\ValueObject\CatalogProductId;

class LogStockOperationCommand
{
    public function __construct(
        public readonly CatalogProductId $productId,
        public readonly int $quantity,
        public readonly string $operation,
        public readonly ?string $reservationId = null,
        public readonly ?array $metadata = null
    ) {}
}
