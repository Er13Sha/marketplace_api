<?php
declare(strict_types=1);

namespace App\Cart\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class CartIsNotActiveException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Cart is not active.',
            statusCode: 409,
            errorCode: 'cart_not_active'
        );
    }
}
