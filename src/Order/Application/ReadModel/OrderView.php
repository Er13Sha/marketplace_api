<?php
declare(strict_types=1);

namespace App\Order\Application\ReadModel;

use App\Order\Domain\Entity\Order;

final class OrderView
{
    /** @param OrderItemView[] $items */
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly ?string $cartId,
        public readonly string $status,
        public readonly array $items,
        public readonly int $itemsCount,
        public readonly int $total,
        public readonly string $currency,
        public readonly array $reservationIds,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Order $order): self
    {
        $items = array_map(
            static fn ($item): OrderItemView => OrderItemView::fromEntity($item),
            $order->getItems()
        );

        return new self(
            id: $order->getId(),
            userId: $order->getUserId(),
            cartId: $order->getCartId(),
            status: $order->getStatus(),
            items: $items,
            itemsCount: $order->getItemsCount(),
            total: $order->getTotal(),
            currency: $order->getCurrency(),
            reservationIds: array_values(array_filter(
                array_map(
                    static fn (OrderItemView $item): ?string => $item->reservationId,
                    $items
                )
            )),
            createdAt: $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $order->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'cart_id' => $this->cartId,
            'status' => $this->status,
            'items' => array_map(
                static fn (OrderItemView $item): array => $item->toArray(),
                $this->items
            ),
            'items_count' => $this->itemsCount,
            'total' => $this->total,
            'currency' => $this->currency,
            'reservation_ids' => $this->reservationIds,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
