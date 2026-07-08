<?php
declare(strict_types=1);

namespace App\Inventory\Application\EventListener;

use App\Catalog\Domain\Event\ProductCreatedEvent;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\CatalogProductId;

class InitializeStockOnProductCreated
{
    public function __construct(
        private StockRepositoryInterface $stockRepository
    ) {}

    public function __invoke(ProductCreatedEvent $event): void
    {
        $this->stockRepository->initialize(
            CatalogProductId::fromString($event->productId->toString()),
            new Quantity($event->initialStock)
        );
    }
}
