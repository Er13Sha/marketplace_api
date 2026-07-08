<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Query\ListCategoriesQuery;
use App\Catalog\Application\ReadModel\CategoryView;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;

final class ListCategoriesHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repository
    ) {}

    /**
     * @return CategoryView[]
     */
    public function __invoke(ListCategoriesQuery $query): array
    {
        return array_map(
            static fn ($category): CategoryView => CategoryView::fromEntity($category),
            $this->repository->findAll($query->limit, $query->offset)
        );
    }
}
