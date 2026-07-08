<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Exception;


use App\Shared\Domain\Exception\AppException;

final class ProductNotFoundException extends AppException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            message: sprintf('Product "%s" was not found.', $productId),
            statusCode: 404,
            errorCode: 'product_not_found'
        );
    }
}
