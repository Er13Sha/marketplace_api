<?php
declare(strict_types=1);

namespace App\Cart\Application\Command;

use App\Catalog\Domain\ValueObject\ProductId;

final class AddCartItemCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly ProductId $productId,
        public readonly int $quantity
    ) {}
}
