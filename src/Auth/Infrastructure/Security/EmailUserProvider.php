<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\InvalidUserIdentityException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class EmailUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $email = User::normalizeEmail($identifier);
        } catch (InvalidUserIdentityException) {
            throw new UserNotFoundException();
        }

        $user = $this->users->findByEmail($email);
        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $refreshedUser = $this->users->findById($user->getId());
        if (!$refreshedUser) {
            throw new UserNotFoundException();
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->changePasswordHash($newHashedPassword);
        $this->users->save($user);
    }
}
