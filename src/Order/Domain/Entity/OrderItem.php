<?php
declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Exception\InvalidOrderItemException;
use Ramsey\Uuid\Uuid;

class OrderItem
{
    private string $id;
    private Order $parentOrder;
    private string $productId;
    private string $productSku;
    private string $productName;
    private int $priceAmount;
    private string $currency;
    private int $quantity;
    private int $lineTotal;
    private ?string $reservationId;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Order $order,
        string $productId,
        string $productSku,
        string $productName,
        int $priceAmount,
        string $currency,
        int $quantity,
        ?string $reservationId = null
    ) {
        if (!Uuid::isValid($productId)) {
            throw new InvalidOrderItemException('Order item product id must be a valid UUID.');
        }

        if ($reservationId !== null && !Uuid::isValid($reservationId)) {
            throw new InvalidOrderItemException('Order item reservation id must be a valid UUID.');
        }

        if (trim($productSku) === '') {
            throw new InvalidOrderItemException('Order item product SKU cannot be blank.');
        }

        if (trim($productName) === '') {
            throw new InvalidOrderItemException('Order item product name cannot be blank.');
        }

        if ($priceAmount < 0) {
            throw new InvalidOrderItemException('Order item price cannot be negative.');
        }

        if ($quantity <= 0) {
            throw new InvalidOrderItemException('Order item quantity must be greater than zero.');
        }

        if (trim($currency) === '') {
            throw new InvalidOrderItemException('Order item currency cannot be blank.');
        }

        $this->id = Uuid::uuid4()->toString();
        $this->parentOrder = $order;
        $this->productId = $productId;
        $this->productSku = $productSku;
        $this->productName = $productName;
        $this->priceAmount = $priceAmount;
        $this->currency = strtoupper($currency);
        $this->quantity = $quantity;
        $this->lineTotal = $priceAmount * $quantity;
        $this->reservationId = $reservationId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->parentOrder;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getPriceAmount(): int
    {
        return $this->priceAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getLineTotal(): int
    {
        return $this->lineTotal;
    }

    public function getReservationId(): ?string
    {
        return $this->reservationId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
