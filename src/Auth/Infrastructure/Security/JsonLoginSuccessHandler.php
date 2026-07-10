<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Application\ReadModel\UserView;
use App\Auth\Domain\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['status' => 'authenticated']);
        }

        return new JsonResponse([
            'user' => UserView::fromEntity($user)->toArray(),
        ]);
    }
}
