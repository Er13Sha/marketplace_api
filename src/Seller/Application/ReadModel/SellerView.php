<?php
declare(strict_types=1);

namespace App\Seller\Application\ReadModel;

use App\Seller\Domain\Entity\Seller;

final class SellerView
{
    public function __construct(
        public readonly string $id,
        public readonly string $ownerUserId,
        public readonly string $displayName,
        public readonly string $legalType,
        public readonly string $taxId,
        public readonly string $phoneNumber,
        public readonly string $address,
        public readonly ?string $bankName,
        public readonly string $bankAccount,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Seller $seller): self
    {
        return new self(
            $seller->getId(),
            $seller->getOwnerUserId(),
            $seller->getDisplayName(),
            $seller->getLegalType(),
            $seller->getTaxId(),
            $seller->getPhoneNumber(),
            $seller->getAddress(),
            $seller->getBankName(),
            $seller->getBankAccount(),
            $seller->getDescription(),
            $seller->getStatus(),
            $seller->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $seller->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_user_id' => $this->ownerUserId,
            'display_name' => $this->displayName,
            'legal_type' => $this->legalType,
            'tax_id' => $this->taxId,
            'phone_number' => $this->phoneNumber,
            'address' => $this->address,
            'bank_name' => $this->bankName,
            'bank_account' => $this->bankAccount,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
