<?php
declare(strict_types=1);

namespace App\Auth\UI\Http;

use App\Auth\Application\Command\RegisterUserCommand;
use App\Auth\Application\ReadModel\UserView;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\UI\Http\Dto\RegisterRequest;
use App\Auth\UI\Http\Dto\SellerProfileRequest;
use App\Seller\Application\ReadModel\SellerView;
use App\Seller\Domain\Entity\Seller;
use App\Seller\Domain\Exception\SellerAlreadyExistsException;
use App\Seller\Domain\Repository\SellerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private UserRepositoryInterface $users,
        private SellerRepositoryInterface $sellers,
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage
    ) {}

    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new RegisterUserCommand(
            $request->email,
            $request->password,
            $request->phoneNumber,
            $request->accountType,
            $request->sellerProfile?->toData()
        ));

        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user instanceof User) {
            throw new \RuntimeException('Registration handler did not return a user.');
        }

        return $this->json($this->authPayload($user), Response::HTTP_CREATED);
    }

    #[Route('/api/auth/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json([
            'error' => 'Send JSON credentials with email and password.',
            'code' => 'invalid_login_request',
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/api/auth/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Authentication required.',
                'code' => 'authentication_required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->authPayload($user));
    }

    #[Route('/api/auth/me/seller', methods: ['POST'])]
    public function becomeSeller(#[MapRequestPayload] SellerProfileRequest $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Authentication required.',
                'code' => 'authentication_required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->em->wrapInTransaction(function () use ($user, $request): void {
            if ($this->sellers->findByOwnerUserId($user->getId()) !== null) {
                throw new SellerAlreadyExistsException($user->getId());
            }

            if (!$user->hasRole(User::ROLE_SELLER)) {
                $user->grantRole(User::ROLE_SELLER);
                $this->users->save($user);
            }

            $this->sellers->save(new Seller($user->getId(), $request->toData()));
        });
        $this->tokenStorage->setToken(new PostAuthenticationToken($user, 'api', $user->getRoles()));

        return $this->json($this->authPayload($user));
    }

    #[Route('/api/auth/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $this->tokenStorage->setToken(null);

        $session = $request->hasSession() ? $request->getSession() : null;
        $sessionName = $session?->getName();
        $session?->invalidate();

        $response = $this->json(['status' => 'logged_out']);
        if ($sessionName !== null) {
            $response->headers->clearCookie($sessionName);
        }

        return $response;
    }

    /** @return array<string,mixed> */
    private function authPayload(User $user): array
    {
        $seller = $this->sellers->findByOwnerUserId($user->getId());

        return [
            'user' => UserView::fromEntity($user)->toArray(),
            'seller' => $seller ? SellerView::fromEntity($seller)->toArray() : null,
        ];
    }
}
