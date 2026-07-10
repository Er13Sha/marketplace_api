<?php
declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Exception\InvalidOrderItemException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;

class Order
{
    public const STATUS_CREATED = 'created';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    private string $id;
    private string $userId;
    private ?string $cartId;
    private string $status;
    private int $total = 0;
    private string $currency = 'KZT';
    /** @var Collection<int, OrderItem> */
    private Collection $items;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $userId, ?string $cartId = null)
    {
        self::assertUuid($userId, 'User id');
        if ($cartId !== null) {
            self::assertUuid($cartId, 'Cart id');
        }

        $this->id = Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->cartId = $cartId;
        $this->status = self::STATUS_CREATED;
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

    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /** @return OrderItem[] */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function getItemsCount(): int
    {
        return array_reduce(
            $this->getItems(),
            static fn (int $sum, OrderItem $item): int => $sum + $item->getQuantity(),
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

    public function addItem(
        string $productId,
        string $productSku,
        string $productName,
        int $priceAmount,
        string $currency,
        int $quantity,
        ?string $reservationId = null
    ): void {
        if ($this->items->count() > 0 && $currency !== $this->currency) {
            throw new InvalidOrderItemException('Order cannot contain items with different currencies.');
        }

        if ($this->items->count() === 0) {
            $this->currency = $currency;
        }

        $item = new OrderItem(
            $this,
            $productId,
            $productSku,
            $productName,
            $priceAmount,
            $currency,
            $quantity,
            $reservationId
        );

        $this->items->add($item);
        $this->total += $item->getLineTotal();
        $this->touch();
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
