<?php
declare(strict_types=1);

namespace App\Order\Application\Command;

final class CheckoutOrderCommand
{
    public function __construct(
        public readonly string $userId
    ) {}
}
