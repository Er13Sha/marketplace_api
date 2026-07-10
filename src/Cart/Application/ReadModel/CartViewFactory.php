<?php
declare(strict_types=1);

namespace App\Cart\Application\ReadModel;

use App\Cart\Domain\Entity\Cart;
use App\Catalog\Application\ReadModel\CategoryView;
use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;

final class CartViewFactory
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private StockRepositoryInterface $stock
    ) {}

    public function fromCart(Cart $cart): CartView
    {
        $items = [];
        $total = 0;
        $currency = 'KZT';

        foreach ($cart->getItems() as $cartItem) {
            $product = $this->products->findById($cartItem->getProductId());
            if (!$product) {
                throw new ProductNotFoundException($cartItem->getProductId()->toString());
            }

            $lineTotal = $product->getPrice()->getAmount() * $cartItem->getQuantity();
            $total += $lineTotal;
            $currency = $product->getPrice()->getCurrency();
            $stock = $this->stock
                ->get(CatalogProductId::fromString($cartItem->getProductId()->toString()))
                ?->getQuantity()
                ->getValue() ?? 0;

            $items[] = new CartItemView(
                productId: $cartItem->getProductId()->toString(),
                sku: $product->getSku()->toString(),
                name: $product->getName(),
                description: $product->getDescription(),
                priceAmount: $product->getPrice()->getAmount(),
                currency: $product->getPrice()->getCurrency(),
                category: $product->getCategory() ? CategoryView::fromEntity($product->getCategory()) : null,
                quantity: $cartItem->getQuantity(),
                lineTotal: $lineTotal,
                stock: $stock,
                createdAt: $cartItem->getCreatedAt()->format(\DateTimeInterface::ATOM),
                updatedAt: $cartItem->getUpdatedAt()->format(\DateTimeInterface::ATOM)
            );
        }

        return new CartView(
            id: $cart->getId(),
            userId: $cart->getUserId(),
            status: $cart->getStatus(),
            items: $items,
            itemsCount: $cart->getItemsCount(),
            total: $total,
            currency: $currency,
            createdAt: $cart->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $cart->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }
}
