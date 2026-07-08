<?php
declare(strict_types=1);


namespace App\Shared\Domain\Exception;

abstract class AppException extends \DomainException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        private readonly string $errorCode = 'app_error',
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
