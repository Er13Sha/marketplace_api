<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Port\ProductCacheInterface;
use App\Catalog\Application\Query\GetProductQuery;
use App\Catalog\Application\ReadModel\ProductView;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class GetProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ProductCacheInterface $cache
    ) {}

    public function __invoke(GetProductQuery $query): ?ProductView
    {
        return $this->cache->get(
            $query->id,
            function () use ($query): ?ProductView {
                $product = $this->repository->findById($query->id);

                return $product ? ProductView::fromEntity($product) : null;
            }
        );
    }
}
