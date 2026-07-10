<?php
declare(strict_types=1);

namespace App\Order\Application\Query;

final class ListOrdersQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly int $limit = 50,
        public readonly int $offset = 0
    ) {}
}
