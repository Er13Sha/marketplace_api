<?php
declare(strict_types=1);

namespace App\Cart\Domain\Entity;

use App\Cart\Domain\Exception\CartIsNotActiveException;
use App\Cart\Domain\Exception\EmptyCartException;
use App\Cart\Domain\Exception\InvalidCartQuantityException;
use App\Catalog\Domain\ValueObject\ProductId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;

class Cart
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CHECKED_OUT = 'checked_out';

    private string $id;
    private string $userId;
    private string $status;
    /** @var Collection<int, CartItem> */
    private Collection $items;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $userId)
    {
        self::assertUuid($userId, 'User id');

        $this->id = Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->status = self::STATUS_ACTIVE;
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** @return CartItem[] */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function getItemsCount(): int
    {
        return array_reduce(
            $this->getItems(),
            static fn (int $sum, CartItem $item): int => $sum + $item->getQuantity(),
            0
        );
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function addItem(ProductId $productId, int $quantity): void
    {
        $this->assertActive();
        self::assertPositiveQuantity($quantity);

        $item = $this->findItem($productId);
        if ($item instanceof CartItem) {
            $item->increaseBy($quantity);
            $this->touch();

            return;
        }

        $this->items->add(new CartItem($this, $productId, $quantity));
        $this->touch();
    }

    public function changeItemQuantity(ProductId $productId, int $quantity): void
    {
        $this->assertActive();

        if ($quantity <= 0) {
            $this->removeItem($productId);

            return;
        }

        $item = $this->findItem($productId);
        if (!$item instanceof CartItem) {
            $this->addItem($productId, $quantity);

            return;
        }

        $item->changeQuantity($quantity);
        $this->touch();
    }

    public function removeItem(ProductId $productId): void
    {
        $this->assertActive();

        $item = $this->findItem($productId);
        if (!$item instanceof CartItem) {
            return;
        }

        $this->items->removeElement($item);
        $this->touch();
    }

    public function clear(): void
    {
        $this->assertActive();

        $this->items->clear();
        $this->touch();
    }

    public function checkout(): void
    {
        $this->assertActive();

        if ($this->items->isEmpty()) {
            throw new EmptyCartException();
        }

        $this->status = self::STATUS_CHECKED_OUT;
        $this->touch();
    }

    private function findItem(ProductId $productId): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->isForProduct($productId)) {
                return $item;
            }
        }

        return null;
    }

    private function assertActive(): void
    {
        if (!$this->isActive()) {
            throw new CartIsNotActiveException();
        }
    }

    private static function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidCartQuantityException();
        }
    }

    private static function assertUuid(string $value, string $label): void
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('%s must be a valid UUID.', $label));
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
