<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Shared\Domain\ValueObject\ProductId;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\ValueObject\Quantity;

interface StockRepositoryInterface
{
    public function get(ProductId $productId): ?Stock;
    public function save(Stock $stock): void;
    public function decrease(ProductId $productId, Quantity $quantity): void;
    public function increase(ProductId $productId, Quantity $quantity): void;
    public function initialize(ProductId $productId, Quantity $initialQuantity): void;
}
