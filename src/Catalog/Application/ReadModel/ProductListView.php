<?php
declare(strict_types=1);

namespace App\Catalog\Application\ReadModel;

final class ProductListView
{
    /**
     * @param ProductView[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total
    ) {}
}
