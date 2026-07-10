<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\ClearCartCommand;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\Domain\Repository\CartRepositoryInterface;

final class ClearCartHandler
{
    public function __construct(
        private CartRepositoryInterface $carts
    ) {}

    public function __invoke(ClearCartCommand $command): CartView
    {
        $cart = $this->carts->findActiveByUserId($command->userId);
        if (!$cart) {
            return CartView::empty($command->userId);
        }

        $cart->clear();
        $this->carts->save($cart);

        return CartView::empty($command->userId);
    }
}
