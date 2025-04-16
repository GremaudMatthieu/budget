<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class ExceptionListener
{
    public function __construct()
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (null !== $exception->getPrevious() && !$exception instanceof UnprocessableEntityHttpException) {
            $exception = $exception->getPrevious();
        }

        $response = new JsonResponse([
            'error' => $exception->getMessage(),
            'type' => $exception::class,
        ], Response::HTTP_BAD_REQUEST);

        if ($exception instanceof HttpExceptionInterface || $exception instanceof \LogicException) {
            if (method_exists($exception, 'getStatusCode')) {
                $response->setStatusCode($exception->getStatusCode());
            } else {
                $response->setStatusCode($exception->getCode());
            }
        } else {
            $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
