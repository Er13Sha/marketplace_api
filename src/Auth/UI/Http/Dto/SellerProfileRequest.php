<?php
declare(strict_types=1);

namespace App\Auth\UI\Http\Dto;

use App\Seller\Application\Dto\SellerProfileData;
use App\Seller\Domain\Entity\Seller;
use Symfony\Component\Validator\Constraints as Assert;

final class SellerProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $displayName,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: [Seller::LEGAL_TYPE_INDIVIDUAL, Seller::LEGAL_TYPE_COMPANY])]
        public readonly string $legalType,

        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public readonly string $taxId,

        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        #[Assert\Regex(pattern: '/^\+?[0-9]{7,20}$/', message: 'Phone number must contain 7-20 digits and may start with +.')]
        public readonly string $phoneNumber,

        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public readonly string $address,

        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public readonly string $bankAccount,

        #[Assert\Length(max: 255)]
        public readonly ?string $bankName = null,

        public readonly ?string $description = null
    ) {}

    public function toData(): SellerProfileData
    {
        return new SellerProfileData(
            $this->displayName,
            $this->legalType,
            $this->taxId,
            $this->phoneNumber,
            $this->address,
            $this->bankName,
            $this->bankAccount,
            $this->description
        );
    }
}
