<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Query\GetCategoryQuery;
use App\Catalog\Application\ReadModel\CategoryView;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;

final class GetCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repository
    ) {}

    public function __invoke(GetCategoryQuery $query): ?CategoryView
    {
        $category = $this->repository->findById($query->id);

        return $category ? CategoryView::fromEntity($category) : null;
    }
}
