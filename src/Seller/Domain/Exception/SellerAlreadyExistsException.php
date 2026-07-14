<?php
declare(strict_types=1);

namespace App\Seller\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class SellerAlreadyExistsException extends AppException
{
    public function __construct(string $ownerUserId)
    {
        parent::__construct(
            message: sprintf('Seller profile for user "%s" already exists.', $ownerUserId),
            statusCode: 409,
            errorCode: 'seller_already_exists'
        );
    }
}
