<?php
declare(strict_types=1);

namespace App\Inventory\Application\Query;

use App\Inventory\Domain\ValueObject\CatalogProductId;

class GetStockQuery
{
    public function __construct(
        public readonly CatalogProductId $productId
    ) {}
}
