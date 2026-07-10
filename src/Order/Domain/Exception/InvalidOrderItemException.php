<?php
declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class InvalidOrderItemException extends AppException
{
    public function __construct(string $message = 'Order item is invalid.')
    {
        parent::__construct(
            message: $message,
            statusCode: 400,
            errorCode: 'invalid_order_item'
        );
    }
}
