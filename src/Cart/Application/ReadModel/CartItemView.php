<?php
declare(strict_types=1);

namespace App\Cart\Application\ReadModel;

use App\Catalog\Application\ReadModel\CategoryView;

final class CartItemView
{
    public function __construct(
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $priceAmount,
        public readonly string $currency,
        public readonly ?CategoryView $category,
        public readonly int $quantity,
        public readonly int $lineTotal,
        public readonly int $stock,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->priceAmount,
            'currency' => $this->currency,
            'category' => $this->category?->toArray(),
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
            'stock' => $this->stock,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
