<?php
declare(strict_types=1);

namespace App\Auth\UI\Http;

use App\Auth\Application\Command\RegisterUserCommand;
use App\Auth\Application\ReadModel\UserView;
use App\Auth\Domain\Entity\User;
use App\Auth\UI\Http\Dto\RegisterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private TokenStorageInterface $tokenStorage
    ) {}

    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new RegisterUserCommand(
            $request->email,
            $request->password,
            $request->phoneNumber
        ));

        $user = $envelope->last(HandledStamp::class)?->getResult();
        if (!$user instanceof User) {
            throw new \RuntimeException('Registration handler did not return a user.');
        }

        return $this->json([
            'user' => UserView::fromEntity($user)->toArray(),
        ], Response::HTTP_CREATED);
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

        return $this->json([
            'user' => UserView::fromEntity($user)->toArray(),
        ]);
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
}
