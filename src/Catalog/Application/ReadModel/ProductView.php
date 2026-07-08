<?php
declare(strict_types=1);

namespace App\Catalog\Application\ReadModel;

use App\Catalog\Domain\Entity\Product;

final class ProductView
{
    public function __construct(
        public readonly string $id,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $priceAmount,
        public readonly string $currency,
        public readonly ?CategoryView $category,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Product $product): self
    {
        return new self(
            $product->getId()->toString(),
            $product->getSku()->toString(),
            $product->getName(),
            $product->getDescription(),
            $product->getPrice()->getAmount(),
            $product->getPrice()->getCurrency(),
            $product->getCategory() ? CategoryView::fromEntity($product->getCategory()) : null,
            $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $product->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->priceAmount,
            'currency' => $this->currency,
            'category' => $this->category?->toArray(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
