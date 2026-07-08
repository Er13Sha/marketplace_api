<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;

class Stock
{
    private CatalogProductId $productId;
    private Quantity $quantity;
    private \DateTimeImmutable $updatedAt;

    public function __construct(CatalogProductId $productId, Quantity $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function restore(CatalogProductId $productId, Quantity $quantity, \DateTimeImmutable $updatedAt): self
    {
        $stock = new self($productId, $quantity);
        $stock->updatedAt = $updatedAt;

        return $stock;
    }

    public function getProductId(): CatalogProductId { return $this->productId; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /**
     * Уменьшает остаток на заданное количество.
     * @throws \DomainException если недостаточно товара
     */
    public function decrease(Quantity $quantity): void
    {
        $newValue = $this->quantity->getValue() - $quantity->getValue();
        if ($newValue < 0) {
            throw new \DomainException('Insufficient stock');
        }
        $this->quantity = new Quantity($newValue);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function increase(Quantity $quantity): void
    {
        $this->quantity = new Quantity($this->quantity->getValue() + $quantity->getValue());
        $this->updatedAt = new \DateTimeImmutable();
    }
}
