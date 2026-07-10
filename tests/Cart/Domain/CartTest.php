<?php
declare(strict_types=1);

namespace App\Tests\Cart\Domain;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Exception\CartIsNotActiveException;
use App\Cart\Domain\Exception\EmptyCartException;
use App\Cart\Domain\Exception\InvalidCartQuantityException;
use App\Catalog\Domain\ValueObject\ProductId;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function testAddItemMergesQuantityForSameProduct(): void
    {
        $cart = new Cart('11111111-1111-4111-8111-111111111111');
        $productId = new ProductId();

        $cart->addItem($productId, 1);
        $cart->addItem($productId, 2);

        self::assertSame(3, $cart->getItemsCount());
        self::assertCount(1, $cart->getItems());
        self::assertSame(3, $cart->getItems()[0]->getQuantity());
    }

    public function testChangeItemQuantityToZeroRemovesItem(): void
    {
        $cart = new Cart('11111111-1111-4111-8111-111111111111');
        $productId = new ProductId();

        $cart->addItem($productId, 2);
        $cart->changeItemQuantity($productId, 0);

        self::assertSame(0, $cart->getItemsCount());
        self::assertSame([], $cart->getItems());
    }

    public function testRejectsNonPositiveAddQuantity(): void
    {
        $cart = new Cart('11111111-1111-4111-8111-111111111111');

        $this->expectException(InvalidCartQuantityException::class);
        $cart->addItem(new ProductId(), 0);
    }

    public function testCheckoutRequiresItems(): void
    {
        $cart = new Cart('11111111-1111-4111-8111-111111111111');

        $this->expectException(EmptyCartException::class);
        $cart->checkout();
    }

    public function testCheckoutMarksCartAsCheckedOutAndBlocksFurtherChanges(): void
    {
        $cart = new Cart('11111111-1111-4111-8111-111111111111');
        $productId = new ProductId();
        $cart->addItem($productId, 1);

        $cart->checkout();

        self::assertFalse($cart->isActive());
        self::assertSame(Cart::STATUS_CHECKED_OUT, $cart->getStatus());

        $this->expectException(CartIsNotActiveException::class);
        $cart->addItem(new ProductId(), 1);
    }
}
