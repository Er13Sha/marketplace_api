<?php
declare(strict_types=1);

namespace App\Order\Application\Query;

final class GetOrderQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly string $orderId
    ) {}
}
