<?php
declare(strict_types=1);

namespace App\Cart\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class EmptyCartException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Cart is empty.',
            statusCode: 409,
            errorCode: 'empty_cart'
        );
    }
}
