<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(ProductId $id): ?Product;

    public function findBySku(Sku $sku): ?Product;

    public function delete(ProductId $id): void;

    public function findByCriteria(array $filters, int $limit, int $offset): array;
}
