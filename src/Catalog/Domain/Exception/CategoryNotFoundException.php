<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class CategoryNotFoundException extends AppException
{
    public function __construct(string $categoryId)
    {
        parent::__construct(
            message: sprintf('Category "%s" was not found.', $categoryId),
            statusCode: 404,
            errorCode: 'category_not_found'
        );
    }
}
