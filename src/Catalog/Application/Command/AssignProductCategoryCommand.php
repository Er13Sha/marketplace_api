<?php
declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\ValueObject\ProductId;

final class AssignProductCategoryCommand
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly ?string $categoryId
    ) {}
}
