<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class JsonAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        return new JsonResponse([
            'error' => 'Access denied.',
            'code' => 'access_denied',
            'status' => Response::HTTP_FORBIDDEN,
        ], Response::HTTP_FORBIDDEN);
    }
}
