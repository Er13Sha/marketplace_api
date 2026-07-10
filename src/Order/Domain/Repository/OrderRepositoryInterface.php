<?php
declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Entity\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(string $id): ?Order;

    public function findByIdForUser(string $id, string $userId): ?Order;

    /** @return Order[] */
    public function findByUserId(string $userId, int $limit, int $offset): array;
}
