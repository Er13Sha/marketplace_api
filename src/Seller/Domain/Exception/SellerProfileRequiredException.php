<?php
declare(strict_types=1);

namespace App\Seller\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class SellerProfileRequiredException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Active seller profile is required.',
            statusCode: 403,
            errorCode: 'seller_profile_required'
        );
    }
}
