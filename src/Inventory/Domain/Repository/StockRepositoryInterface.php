<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\ValueObject\Quantity;

interface StockRepositoryInterface
{
    public function get(CatalogProductId $productId): ?Stock;
    public function save(Stock $stock): void;
    public function decrease(CatalogProductId $productId, Quantity $quantity): void;
    public function increase(CatalogProductId $productId, Quantity $quantity): void;
    public function initialize(CatalogProductId $productId, Quantity $initialQuantity): void;
}
