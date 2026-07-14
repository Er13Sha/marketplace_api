<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Catalog\Domain\ValueObject\Price;
use App\Catalog\Domain\Event\ProductCreatedEvent;
use App\Catalog\Domain\Event\ProductUpdatedEvent;

class Product
{
    private ProductId $id;
    private Sku $sku;
    private string $name;
    private ?string $description;
    private ?Category $category = null;
    private Price $price;
    private ?string $sellerId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @var object[] не маппится Doctrine; гидрация не вызывает конструктор */
    private array $domainEvents = [];

    // Конструктор для нового продукта
    public function __construct(Sku $sku, string $name, Price $price, int $initialStock, ?string $description = null, ?Category $category = null, ?string $sellerId = null)
    {
        if ($initialStock < 0) {
            throw new \DomainException('Initial stock cannot be negative');
        }

        if ($sellerId !== null && trim($sellerId) === '') {
            throw new \DomainException('Seller id cannot be blank');
        }

        $this->id = new ProductId();
        $this->sku = $sku;
        $this->name = $name;
        $this->category = $category;
        $this->price = $price;
        $this->sellerId = $sellerId;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new ProductCreatedEvent(
            $this->id,
            $this->sku->toString(),
            $this->name,
            $this->price->getAmount(),
            $initialStock,
            $this->createdAt
        ));
    }

    // Геттеры (все поля только для чтения)
    public function getId(): ProductId { return $this->id; }
    public function getSku(): Sku { return $this->sku; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategory(): ?Category { return $this->category; }
    public function getPrice(): Price { return $this->price; }
    public function getSellerId(): ?string { return $this->sellerId; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Методы изменения (защищают инварианты)
    public function updateDetails(string $name, ?string $description, Price $price, ?Category $category = null): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordUpdated();
    }

    public function assignCategory(?Category $category): void
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordUpdated();
    }

    // Domain events
    private function recordThat(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    private function recordUpdated(): void
    {
        $this->recordThat(new ProductUpdatedEvent(
            $this->id,
            $this->name,
            $this->price->getAmount(),
            $this->updatedAt
        ));
    }

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
