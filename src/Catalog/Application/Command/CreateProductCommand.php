<?php
declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\ValueObject\Sku;
use App\Catalog\Domain\ValueObject\Price;

class CreateProductCommand
{
    public function __construct(
        public readonly Sku $sku,
        public readonly string $name,
        public readonly Price $price,
        public readonly int $initialStock,
        public readonly ?string $description,
        public readonly ?string $categoryId = null,
        public readonly ?string $sellerId = null
    ) {}
}
