<?php
declare(strict_types=1);

namespace App\Cart\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class InvalidCartQuantityException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Cart item quantity must be greater than zero.',
            statusCode: 400,
            errorCode: 'invalid_cart_quantity'
        );
    }
}
