<?php
declare(strict_types=1);

namespace App\Tests\Order\Domain;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\InvalidOrderItemException;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testAddItemStoresSnapshotAndTotals(): void
    {
        $order = new Order(
            '11111111-1111-4111-8111-111111111111',
            '22222222-2222-4222-8222-222222222222'
        );

        $order->addItem(
            productId: '33333333-3333-4333-8333-333333333333',
            productSku: 'SKU12345',
            productName: 'Snapshot product',
            priceAmount: 1500,
            currency: 'KZT',
            quantity: 2,
            reservationId: '44444444-4444-4444-8444-444444444444'
        );

        self::assertSame(Order::STATUS_CREATED, $order->getStatus());
        self::assertSame(2, $order->getItemsCount());
        self::assertSame(3000, $order->getTotal());
        self::assertSame('KZT', $order->getCurrency());
        self::assertCount(1, $order->getItems());

        $item = $order->getItems()[0];
        self::assertSame('33333333-3333-4333-8333-333333333333', $item->getProductId());
        self::assertSame('SKU12345', $item->getProductSku());
        self::assertSame('Snapshot product', $item->getProductName());
        self::assertSame(1500, $item->getPriceAmount());
        self::assertSame(2, $item->getQuantity());
        self::assertSame(3000, $item->getLineTotal());
        self::assertSame('44444444-4444-4444-8444-444444444444', $item->getReservationId());
    }

    public function testRejectsItemsWithDifferentCurrencies(): void
    {
        $order = new Order('11111111-1111-4111-8111-111111111111');
        $order->addItem(
            '33333333-3333-4333-8333-333333333333',
            'SKU12345',
            'First',
            1000,
            'KZT',
            1
        );

        $this->expectException(InvalidOrderItemException::class);
        $order->addItem(
            '55555555-5555-4555-8555-555555555555',
            'SKU54321',
            'Second',
            1000,
            'USD',
            1
        );
    }

    public function testRejectsInvalidItemQuantity(): void
    {
        $order = new Order('11111111-1111-4111-8111-111111111111');

        $this->expectException(InvalidOrderItemException::class);
        $order->addItem(
            '33333333-3333-4333-8333-333333333333',
            'SKU12345',
            'Product',
            1000,
            'KZT',
            0
        );
    }
}
