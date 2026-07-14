<?php
declare(strict_types=1);

namespace App\Seller\Application\Dto;

final class SellerProfileData
{
    public function __construct(
        public readonly string $displayName,
        public readonly string $legalType,
        public readonly string $taxId,
        public readonly string $phoneNumber,
        public readonly string $address,
        public readonly ?string $bankName,
        public readonly string $bankAccount,
        public readonly ?string $description
    ) {}
}
