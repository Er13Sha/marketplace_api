<?php
declare(strict_types=1);

namespace App\Cart\Application\ReadModel;

final class CartView
{
    /** @param CartItemView[] $items */
    public function __construct(
        public readonly ?string $id,
        public readonly string $userId,
        public readonly string $status,
        public readonly array $items,
        public readonly int $itemsCount,
        public readonly int $total,
        public readonly string $currency,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt
    ) {}

    public static function empty(string $userId): self
    {
        return new self(
            id: null,
            userId: $userId,
            status: 'active',
            items: [],
            itemsCount: 0,
            total: 0,
            currency: 'KZT',
            createdAt: null,
            updatedAt: null
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'status' => $this->status,
            'items' => array_map(
                static fn (CartItemView $item): array => $item->toArray(),
                $this->items
            ),
            'items_count' => $this->itemsCount,
            'total' => $this->total,
            'currency' => $this->currency,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
