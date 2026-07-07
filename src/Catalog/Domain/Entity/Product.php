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
    private Price $price;
    private int $stock;          // временно, пока инвентарь не выделен
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @var object[] не маппится Doctrine; гидрация не вызывает конструктор */
    private array $domainEvents = [];

    // Конструктор для нового продукта
    public function __construct(Sku $sku, string $name, Price $price, int $stock, ?string $description = null)
    {
        $this->id = new ProductId();
        $this->sku = $sku;
        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new ProductCreatedEvent(
            $this->id,
            $this->sku->toString(),
            $this->name,
            $this->price->getAmount(),
            $this->stock,
            $this->createdAt
        ));
    }

    // Геттеры (все поля только для чтения)
    public function getId(): ProductId { return $this->id; }
    public function getSku(): Sku { return $this->sku; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getPrice(): Price { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Методы изменения (защищают инварианты)
    public function updateDetails(string $name, ?string $description, Price $price): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordUpdated();
    }

    public function updateStock(int $newStock): void
    {
        if ($newStock < 0) throw new \DomainException('Stock cannot be negative');
        $this->stock = $newStock;
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
            $this->stock,
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
