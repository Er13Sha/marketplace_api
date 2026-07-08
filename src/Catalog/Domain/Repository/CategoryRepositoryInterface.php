<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Category;

interface CategoryRepositoryInterface
{
    public function save(Category $category): void;

    public function findById(string $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    /**
     * @return Category[]
     */
    public function findAll(int $limit, int $offset): array;
}
