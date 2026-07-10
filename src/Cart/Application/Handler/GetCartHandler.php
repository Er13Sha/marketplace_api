<?php
declare(strict_types=1);

namespace App\Cart\Application\Handler;

use App\Cart\Application\Query\GetCartQuery;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\Application\ReadModel\CartViewFactory;
use App\Cart\Domain\Repository\CartRepositoryInterface;

final class GetCartHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private CartViewFactory $cartViewFactory
    ) {}

    public function __invoke(GetCartQuery $query): CartView
    {
        $cart = $this->carts->findActiveByUserId($query->userId);
        if (!$cart) {
            return CartView::empty($query->userId);
        }

        return $this->cartViewFactory->fromCart($cart);
    }
}
