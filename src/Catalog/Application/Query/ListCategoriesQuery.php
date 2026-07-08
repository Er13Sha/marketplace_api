<?php
declare(strict_types=1);

namespace App\Catalog\Application\Query;

final class ListCategoriesQuery
{
    public function __construct(
        public readonly int $limit,
        public readonly int $offset
    ) {}
}
