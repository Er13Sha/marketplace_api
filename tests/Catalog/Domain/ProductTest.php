<?php
declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Event\ProductCreatedEvent;
use App\Catalog\Domain\Event\ProductUpdatedEvent;
use App\Catalog\Domain\ValueObject\Price;
use App\Catalog\Domain\ValueObject\Sku;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    private function makeProduct(): Product
    {
        return new Product(new Sku('ABC12345'), 'Widget', new Price(1000, 'KZT'), 7, 'desc');
    }

    public function testConstructorRecordsProductCreatedEvent(): void
    {
        $product = $this->makeProduct();

        $events = $product->pullDomainEvents();

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(ProductCreatedEvent::class, $event);
        self::assertTrue($event->productId->equals($product->getId()));
        self::assertSame('ABC12345', $event->sku);
        self::assertSame('Widget', $event->name);
        self::assertSame(1000, $event->priceAmount);
        self::assertSame(7, $event->initialStock);
        self::assertEquals($product->getCreatedAt(), $event->occurredAt);
    }

    public function testPullDomainEventsClearsBuffer(): void
    {
        $product = $this->makeProduct();

        $product->pullDomainEvents();

        self::assertSame([], $product->pullDomainEvents());
    }

    public function testConstructorRejectsNegativeInitialStock(): void
    {
        $this->expectException(\DomainException::class);
        new Product(new Sku('ABC12345'), 'Widget', new Price(1000, 'KZT'), -1);
    }

    public function testUpdateDetailsRecordsProductUpdatedEvent(): void
    {
        $product = $this->makeProduct();
        $product->pullDomainEvents();

        $product->updateDetails('Renamed', null, new Price(2500, 'KZT'));

        $events = $product->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ProductUpdatedEvent::class, $events[0]);
        self::assertSame('Renamed', $events[0]->name);
        self::assertSame(2500, $events[0]->priceAmount);
        self::assertSame('Renamed', $product->getName());
        self::assertNull($product->getDescription());
    }
}
