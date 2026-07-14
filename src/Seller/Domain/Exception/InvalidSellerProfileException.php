<?php
declare(strict_types=1);

namespace App\Seller\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class InvalidSellerProfileException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            statusCode: 400,
            errorCode: 'invalid_seller_profile'
        );
    }
}
