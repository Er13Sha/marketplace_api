<?php
declare(strict_types=1);

namespace App\Auth\UI\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public readonly string $password,

        #[Assert\Length(max: 32)]
        #[Assert\Regex(pattern: '/^\+?[0-9]{7,20}$/', message: 'Phone number must contain 7-20 digits and may start with +.')]
        public readonly ?string $phoneNumber = null
    ) {}
}
