<?php
declare(strict_types=1);

namespace App\Auth\Application\Command;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $plainPassword,
        public readonly ?string $phoneNumber = null
    ) {}
}
