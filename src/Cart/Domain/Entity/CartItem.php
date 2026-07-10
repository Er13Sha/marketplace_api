<?php
declare(strict_types=1);

namespace App\Cart\Domain\Entity;

use App\Cart\Domain\Exception\InvalidCartQuantityException;
use App\Catalog\Domain\ValueObject\ProductId;
use Ramsey\Uuid\Uuid;

class CartItem
{
    private string $id;
    private Cart $cart;
    private ProductId $productId;
    private int $quantity;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(Cart $cart, ProductId $productId, int $quantity)
    {
        $this->assertPositiveQuantity($quantity);

        $this->id = Uuid::uuid4()->toString();
        $this->cart = $cart;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isForProduct(ProductId $productId): bool
    {
        return $this->productId->equals($productId);
    }

    public function increaseBy(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        $this->quantity += $quantity;
        $this->touch();
    }

    public function changeQuantity(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        $this->quantity = $quantity;
        $this->touch();
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidCartQuantityException();
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
