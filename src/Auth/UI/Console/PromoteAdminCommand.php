<?php
declare(strict_types=1);

namespace App\Auth\UI\Console;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\InvalidUserIdentityException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auth:promote-admin',
    description: 'Grant ROLE_ADMIN to an existing user by email.'
)]
final class PromoteAdminCommand extends Command
{
    public function __construct(private UserRepositoryInterface $users)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        try {
            $user = $this->users->findByEmail($email);
        } catch (InvalidUserIdentityException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (!$user) {
            $io->error(sprintf('User "%s" was not found.', $email));

            return Command::FAILURE;
        }

        if ($user->hasRole(User::ROLE_ADMIN)) {
            $io->success(sprintf('User "%s" already has ROLE_ADMIN.', $user->getEmail()));

            return Command::SUCCESS;
        }

        $user->grantRole(User::ROLE_ADMIN);
        $this->users->save($user);

        $io->success(sprintf('ROLE_ADMIN granted to "%s".', $user->getEmail()));

        return Command::SUCCESS;
    }
}
