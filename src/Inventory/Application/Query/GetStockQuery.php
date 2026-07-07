<?php
declare(strict_types=1);

namespace App\Inventory\Application\Query;

use App\Shared\Domain\ValueObject\ProductId;

class GetStockQuery
{
    public function __construct(
        public readonly ProductId $productId
    ) {}
}
