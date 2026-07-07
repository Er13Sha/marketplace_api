<?php
declare(strict_types=1);

namespace App\Catalog\Application\Query;

use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    /**
     * @return ProductView[]
     */
    public function __invoke(ListProductsQuery $query): array
    {
        $filters = [];

        if ($query->name !== null && $query->name !== '') {
            $filters['name'] = $query->name;
        }

        return array_map(
            static fn ($product): ProductView => ProductView::fromEntity($product),
            $this->repository->findByCriteria($filters, $query->limit, $query->offset)
        );
    }
}
