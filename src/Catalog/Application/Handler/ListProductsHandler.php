<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Query\ListProductsQuery;
use App\Catalog\Application\ReadModel\ProductListView;
use App\Catalog\Application\ReadModel\ProductView;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function __invoke(ListProductsQuery $query): ProductListView
    {
        $filters = [];

        if ($query->name !== null && $query->name !== '') {
            $filters['name'] = $query->name;
        }

        if ($query->categoryId !== null && $query->categoryId !== '') {
            $filters['categoryId'] = $query->categoryId;
        }

        if ($query->sellerId !== null && $query->sellerId !== '') {
            $filters['sellerId'] = $query->sellerId;
        }

        $items = array_map(
            static fn ($product): ProductView => ProductView::fromEntity($product),
            $this->repository->findByCriteria($filters, $query->limit, $query->offset)
        );

        return new ProductListView(
            $items,
            $this->repository->countByCriteria($filters)
        );
    }
}
