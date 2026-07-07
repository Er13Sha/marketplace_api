<?php
declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Shared\Domain\ValueObject\ProductId;

class LogStockOperationCommand
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly int $quantity,
        public readonly string $operation,
        public readonly ?string $reservationId = null,
        public readonly ?array $metadata = null
    ) {}
}
