<?php
declare(strict_types=1);

namespace App\Auth\Application\ReadModel;

use App\Auth\Domain\Entity\User;

final class UserView
{
    /** @param string[] $roles */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $phoneNumber,
        public readonly array $roles,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getEmail(),
            $user->getPhoneNumber(),
            $user->getRoles(),
            $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $user->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'roles' => $this->roles,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
