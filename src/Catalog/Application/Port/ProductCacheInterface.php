<?php
declare(strict_types=1);

namespace App\Catalog\Application\Port;

use App\Catalog\Application\Query\ProductView;
use App\Catalog\Domain\ValueObject\ProductId;

interface ProductCacheInterface
{
    /**
     * @param callable():?ProductView $fetcher
     */
    public function get(ProductId $id, callable $fetcher): ?ProductView;

    public function invalidate(ProductId $id): void;
}
