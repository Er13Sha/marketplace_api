<?php
declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\Exception\InvalidUserIdentityException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    private string $id;
    private string $email;
    private ?string $phoneNumber;
    private string $passwordHash = '';
    /** @var string[] */
    private array $roles = [];
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @param string[] $roles */
    public function __construct(string $email, ?string $phoneNumber = null, array $roles = ['ROLE_USER'])
    {
        $this->id = Uuid::uuid4()->toString();
        $this->email = self::normalizeEmail($email);
        $this->phoneNumber = self::normalizePhoneNumber($phoneNumber);
        $this->assignRoles($roles);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return string[] */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_USER;

        return array_values(array_unique($roles));
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changePasswordHash(string $passwordHash): void
    {
        if (trim($passwordHash) === '') {
            throw new InvalidUserIdentityException('Password hash cannot be blank.');
        }

        $this->passwordHash = $passwordHash;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changePhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = self::normalizePhoneNumber($phoneNumber);
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @param string[] $roles */
    public function assignRoles(array $roles): void
    {
        $normalizedRoles = [];
        foreach ($roles as $role) {
            $normalizedRoles[] = self::normalizeRole($role);
        }

        $this->roles = array_values(array_unique($normalizedRoles));
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function grantRole(string $role): void
    {
        $this->assignRoles([...$this->roles, $role]);
    }

    public function revokeRole(string $role): void
    {
        $role = self::normalizeRole($role);
        $this->assignRoles(array_values(array_filter(
            $this->roles,
            static fn (string $currentRole): bool => $currentRole !== $role
        )));
    }

    public function hasRole(string $role): bool
    {
        return in_array(self::normalizeRole($role), $this->getRoles(), true);
    }

    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidUserIdentityException('Invalid user email.');
        }

        return $email;
    }

    public static function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        $phoneNumber = trim($phoneNumber);
        if ($phoneNumber === '') {
            return null;
        }

        if (!preg_match('/^\+?[0-9]{7,20}$/', $phoneNumber)) {
            throw new InvalidUserIdentityException('Invalid user phone number.');
        }

        return $phoneNumber;
    }

    private static function normalizeRole(string $role): string
    {
        $role = strtoupper(trim($role));
        if ($role === '') {
            throw new InvalidUserIdentityException('User role cannot be blank.');
        }

        if (!str_starts_with($role, 'ROLE_')) {
            throw new InvalidUserIdentityException('User role must start with ROLE_.');
        }

        return $role;
    }

    /** @return array<string,mixed> */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'passwordHash' => hash('crc32c', $this->passwordHash),
            'roles' => $this->roles,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /** @param array<string,mixed> $data */
    public function __unserialize(array $data): void
    {
        $this->id = (string) $data['id'];
        $this->email = (string) $data['email'];
        $this->phoneNumber = $data['phoneNumber'] !== null ? (string) $data['phoneNumber'] : null;
        $this->passwordHash = (string) $data['passwordHash'];
        $this->roles = is_array($data['roles']) ? $data['roles'] : [];
        $this->createdAt = $data['createdAt'] instanceof \DateTimeImmutable
            ? $data['createdAt']
            : new \DateTimeImmutable((string) $data['createdAt']);
        $this->updatedAt = $data['updatedAt'] instanceof \DateTimeImmutable
            ? $data['updatedAt']
            : new \DateTimeImmutable((string) $data['updatedAt']);
    }
}
