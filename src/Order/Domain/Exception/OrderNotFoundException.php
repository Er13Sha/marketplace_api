<?php
declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class OrderNotFoundException extends AppException
{
    public function __construct(string $orderId)
    {
        parent::__construct(
            message: sprintf('Order "%s" was not found.', $orderId),
            statusCode: 404,
            errorCode: 'order_not_found'
        );
    }
}
