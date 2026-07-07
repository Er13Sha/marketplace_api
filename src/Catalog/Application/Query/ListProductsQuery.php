<?php
declare(strict_types=1);

namespace App\Catalog\Application\Query;

final class ListProductsQuery
{
    public function __construct(
        public readonly ?string $name,
        public readonly int $limit,
        public readonly int $offset
    ) {}
}
