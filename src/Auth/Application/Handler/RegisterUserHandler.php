<?php
declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\RegisterUserCommand;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Seller\Domain\Entity\Seller;
use App\Seller\Domain\Exception\SellerProfileRequiredException;
use App\Seller\Domain\Repository\SellerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private SellerRepositoryInterface $sellers,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(RegisterUserCommand $command): User
    {
        $email = User::normalizeEmail($command->email);
        if ($this->users->findByEmail($email) !== null) {
            throw new UserAlreadyExistsException($email);
        }

        $roles = match (strtolower(trim($command->accountType))) {
            'customer' => [User::ROLE_USER],
            'seller' => [User::ROLE_USER, User::ROLE_SELLER],
            default => throw new \InvalidArgumentException('Unsupported account type.'),
        };

        if (strtolower(trim($command->accountType)) === 'seller' && $command->sellerProfile === null) {
            throw new SellerProfileRequiredException();
        }

        return $this->em->wrapInTransaction(function () use ($command, $email, $roles): User {
            $user = new User($email, $command->phoneNumber, $roles);
            $user->changePasswordHash(
                $this->passwordHasher->hashPassword($user, $command->plainPassword)
            );

            $this->users->save($user);

            if (strtolower(trim($command->accountType)) === 'seller' && $command->sellerProfile !== null) {
                $this->sellers->save(new Seller($user->getId(), $command->sellerProfile));
            }

            return $user;
        });
    }
}
