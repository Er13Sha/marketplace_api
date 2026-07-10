<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\RemoveCartItemCommand;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\Application\ReadModel\CartViewFactory;
use App\Cart\Domain\Repository\CartRepositoryInterface;

final class RemoveCartItemHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private CartViewFactory $cartViewFactory
    ) {}

    public function __invoke(RemoveCartItemCommand $command): CartView
    {
        $cart = $this->carts->findActiveByUserId($command->userId);
        if (!$cart) {
            return CartView::empty($command->userId);
        }

        $cart->removeItem($command->productId);
        $this->carts->save($cart);

        return $cart->getItemsCount() === 0
            ? CartView::empty($command->userId)
            : $this->cartViewFactory->fromCart($cart);
    }
}
