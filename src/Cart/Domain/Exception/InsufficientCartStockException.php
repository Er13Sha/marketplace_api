<?php
declare(strict_types=1);

namespace App\Cart\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class InsufficientCartStockException extends AppException
{
    public function __construct(string $productId, int $availableQuantity)
    {
        parent::__construct(
            message: sprintf('Product "%s" has only %d item(s) available.', $productId, $availableQuantity),
            statusCode: 409,
            errorCode: 'insufficient_cart_stock'
        );
    }
}
