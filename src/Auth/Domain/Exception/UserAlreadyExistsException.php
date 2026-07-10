<?php
declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\AppException;

final class UserAlreadyExistsException extends AppException
{
    public function __construct(string $email)
    {
        parent::__construct(
            message: sprintf('User with email "%s" already exists.', $email),
            statusCode: 409,
            errorCode: 'user_already_exists'
        );
    }
}
