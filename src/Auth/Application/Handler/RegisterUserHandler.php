<?php
declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\RegisterUserCommand;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function __invoke(RegisterUserCommand $command): User
    {
        $email = User::normalizeEmail($command->email);
        if ($this->users->findByEmail($email) !== null) {
            throw new UserAlreadyExistsException($email);
        }

        $user = new User($email, $command->phoneNumber);
        $user->changePasswordHash(
            $this->passwordHasher->hashPassword($user, $command->plainPassword)
        );

        $this->users->save($user);

        return $user;
    }
}
