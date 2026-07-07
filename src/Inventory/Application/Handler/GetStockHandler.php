<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Query\GetStockQuery;
use App\Inventory\Domain\Repository\StockRepositoryInterface;

class GetStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository
    ) {}

    public function __invoke(GetStockQuery $query): ?array
    {
        $stock = $this->stockRepository->get($query->productId);
        if (!$stock) {
            return null;
        }
        return [
            'product_id' => $stock->getProductId()->toString(),
            'quantity' => $stock->getQuantity()->getValue(),
            'updated_at' => $stock->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
