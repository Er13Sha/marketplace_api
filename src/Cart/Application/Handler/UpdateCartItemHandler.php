<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\UpdateCartItemCommand;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\Application\ReadModel\CartViewFactory;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final class UpdateCartItemHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private CartStockGuard $stockGuard,
        private CartViewFactory $cartViewFactory
    ) {}

    public function __invoke(UpdateCartItemCommand $command): CartView
    {
        if (!$this->products->findById($command->productId)) {
            throw new ProductNotFoundException($command->productId->toString());
        }

        $cart = $this->carts->findActiveByUserId($command->userId);
        if (!$cart && $command->quantity <= 0) {
            return CartView::empty($command->userId);
        }

        $cart ??= new Cart($command->userId);
        if ($command->quantity > 0) {
            $this->stockGuard->assertAvailable($command->productId, $command->quantity);
        }

        $cart->changeItemQuantity($command->productId, $command->quantity);
        $this->carts->save($cart);

        return $cart->getItemsCount() === 0
            ? CartView::empty($command->userId)
            : $this->cartViewFactory->fromCart($cart);
    }
}
