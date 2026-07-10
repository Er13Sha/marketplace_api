<?php
declare(strict_types=1);

namespace App\Order\Application\ReadModel;

use App\Order\Domain\Entity\OrderItem;

final class OrderItemView
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $currency,
        public readonly int $quantity,
        public readonly int $lineTotal,
        public readonly ?string $reservationId,
        public readonly string $createdAt
    ) {}

    public static function fromEntity(OrderItem $item): self
    {
        return new self(
            id: $item->getId(),
            productId: $item->getProductId(),
            sku: $item->getProductSku(),
            name: $item->getProductName(),
            priceAmount: $item->getPriceAmount(),
            currency: $item->getCurrency(),
            quantity: $item->getQuantity(),
            lineTotal: $item->getLineTotal(),
            reservationId: $item->getReservationId(),
            createdAt: $item->getCreatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->priceAmount,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
            'reservation_id' => $this->reservationId,
            'created_at' => $this->createdAt,
        ];
    }
}
