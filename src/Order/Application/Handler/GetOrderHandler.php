<?php
declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Order\Application\Query\GetOrderQuery;
use App\Order\Application\ReadModel\OrderView;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Repository\OrderRepositoryInterface;

final class GetOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders
    ) {}

    public function __invoke(GetOrderQuery $query): OrderView
    {
        $order = $this->orders->findByIdForUser($query->orderId, $query->userId);
        if (!$order) {
            throw new OrderNotFoundException($query->orderId);
        }

        return OrderView::fromEntity($order);
    }
}
