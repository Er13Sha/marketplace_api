<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\AddCartItemCommand;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\Application\ReadModel\CartViewFactory;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class AddCartItemHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private CartStockGuard $stockGuard,
        private CartViewFactory $cartViewFactory
    ) {}

    public function __invoke(AddCartItemCommand $command): CartView
    {
        if (!$this->products->findById($command->productId)) {
            throw new ProductNotFoundException($command->productId->toString());
        }

        $cart = $this->carts->findActiveByUserId($command->userId) ?? new Cart($command->userId);
        $requestedQuantity = $this->quantityInCart($cart, $command->productId->toString()) + $command->quantity;
        $this->stockGuard->assertAvailable($command->productId, $requestedQuantity);

        $cart->addItem($command->productId, $command->quantity);
        $this->carts->save($cart);

        return $this->cartViewFactory->fromCart($cart);
    }

    private function quantityInCart(Cart $cart, string $productId): int
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProductId()->toString() === $productId) {
                return $item->getQuantity();
            }
        }

        return 0;
    }
}
