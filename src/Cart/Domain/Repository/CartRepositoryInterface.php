<?php
declare(strict_types=1);

namespace App\Cart\Domain\Repository;

use App\Cart\Domain\Entity\Cart;

interface CartRepositoryInterface
{
    public function save(Cart $cart): void;

    public function findById(string $id): ?Cart;

    public function findActiveByUserId(string $userId): ?Cart;
}
