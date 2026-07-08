<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use App\Shared\Domain\Exception\AppException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

// Registered explicitly in config/services.yaml on kernel.exception at priority -100:
// runs AFTER the framework's logKernelException (0) so errors are still logged, but
// BEFORE its renderer (-128). stopPropagation() then prevents that renderer — which
// under Swoole's "cli" SAPI dumps a VarDumper stack trace into the HTTP response, even
// in prod — from ever running.
final class ApiExceptionListener
{
    public function __construct(
        private bool $debug,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $this->unwrapMessengerException($event->getThrowable());

        $status = $throwable instanceof AppException
            ? $throwable->statusCode()
            : ($throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR);

        $headers = $throwable instanceof HttpExceptionInterface
            ? $throwable->getHeaders()
            : [];

        $payload = [
            'error'  => $throwable instanceof AppException
                ? $throwable->getMessage()
                : (Response::$statusTexts[$status] ?? 'Error'),
            'status' => $status,
        ];

        if ($throwable instanceof AppException) {
            $payload['code'] = $throwable->errorCode();
        }

        if ($this->debug) {
            $payload['message']   = $throwable->getMessage();
            $payload['exception'] = $throwable::class;
        } elseif ($status < 500 && '' !== $throwable->getMessage()) {
            // 4xx messages describe the client's request, not server internals — safe to expose.
            $payload['message'] = $throwable->getMessage();
        }

        $event->setResponse(new JsonResponse($payload, $status, $headers));
        $event->stopPropagation();
    }

    private function unwrapMessengerException(\Throwable $throwable): \Throwable
    {
        if (!$throwable instanceof HandlerFailedException) {
            return $throwable;
        }

        return $throwable->getWrappedExceptions()[0]
            ?? $throwable->getPrevious()
            ?? $throwable;
    }
}
