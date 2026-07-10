<?php
declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Order\Application\Query\ListOrdersQuery;
use App\Order\Application\ReadModel\OrderView;
use App\Order\Domain\Repository\OrderRepositoryInterface;

final class ListOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders
    ) {}

    /** @return OrderView[] */
    public function __invoke(ListOrdersQuery $query): array
    {
        return array_map(
            static fn ($order): OrderView => OrderView::fromEntity($order),
            $this->orders->findByUserId($query->userId, $query->limit, $query->offset)
        );
    }
}
