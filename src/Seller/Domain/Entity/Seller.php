<?php
declare(strict_types=1);

namespace App\Seller\Domain\Entity;

use App\Seller\Application\Dto\SellerProfileData;
use App\Seller\Domain\Exception\InvalidSellerProfileException;
use Ramsey\Uuid\Uuid;

final class Seller
{
    public const STATUS_ACTIVE = 'active';
    public const LEGAL_TYPE_INDIVIDUAL = 'individual';
    public const LEGAL_TYPE_COMPANY = 'company';

    private string $id;
    private string $ownerUserId;
    private string $displayName;
    private string $legalType;
    private string $taxId;
    private string $phoneNumber;
    private string $address;
    private ?string $bankName;
    private string $bankAccount;
    private ?string $description;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $ownerUserId, SellerProfileData $profile)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->ownerUserId = self::normalizeUuid($ownerUserId);
        $this->status = self::STATUS_ACTIVE;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->applyProfile($profile);
    }

    public function updateProfile(SellerProfileData $profile): void
    {
        $this->applyProfile($profile);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getOwnerUserId(): string { return $this->ownerUserId; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getLegalType(): string { return $this->legalType; }
    public function getTaxId(): string { return $this->taxId; }
    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function getAddress(): string { return $this->address; }
    public function getBankName(): ?string { return $this->bankName; }
    public function getBankAccount(): string { return $this->bankAccount; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    private function applyProfile(SellerProfileData $profile): void
    {
        $this->displayName = self::required($profile->displayName, 'Seller display name');
        $this->legalType = self::normalizeLegalType($profile->legalType);
        $this->taxId = self::required($profile->taxId, 'Seller tax id');
        $this->phoneNumber = self::normalizePhoneNumber($profile->phoneNumber);
        $this->address = self::required($profile->address, 'Seller address');
        $this->bankName = self::nullable($profile->bankName);
        $this->bankAccount = self::required($profile->bankAccount, 'Seller bank account');
        $this->description = self::nullable($profile->description);
    }

    private static function normalizeUuid(string $value): string
    {
        $value = trim($value);
        if (!Uuid::isValid($value)) {
            throw new InvalidSellerProfileException('Seller owner user id must be a valid UUID.');
        }

        return $value;
    }

    private static function normalizeLegalType(string $legalType): string
    {
        $legalType = strtolower(trim($legalType));
        if (!in_array($legalType, [self::LEGAL_TYPE_INDIVIDUAL, self::LEGAL_TYPE_COMPANY], true)) {
            throw new InvalidSellerProfileException('Seller legal type is invalid.');
        }

        return $legalType;
    }

    private static function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = self::required($phoneNumber, 'Seller phone number');
        if (!preg_match('/^\+?[0-9]{7,20}$/', $phoneNumber)) {
            throw new InvalidSellerProfileException('Seller phone number is invalid.');
        }

        return $phoneNumber;
    }

    private static function required(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidSellerProfileException(sprintf('%s cannot be blank.', $label));
        }

        return $value;
    }

    private static function nullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
