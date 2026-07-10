<?php
declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class InvalidUserIdentityException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            statusCode: 400,
            errorCode: 'invalid_user_identity'
        );
    }
}
