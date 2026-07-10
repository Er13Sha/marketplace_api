<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Domain\Exception\InsufficientCartStockException;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;

final class CartStockGuard
{
    public function __construct(
        private StockRepositoryInterface $stock
    ) {}

    public function assertAvailable(ProductId $productId, int $requestedQuantity): void
    {
        $availableQuantity = $this->stock
            ->get(CatalogProductId::fromString($productId->toString()))
            ?->getQuantity()
            ->getValue() ?? 0;

        if ($requestedQuantity > $availableQuantity) {
            throw new InsufficientCartStockException($productId->toString(), $availableQuantity);
        }
    }
}
