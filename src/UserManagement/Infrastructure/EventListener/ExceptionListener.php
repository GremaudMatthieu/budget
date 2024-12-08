<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

readonly class ExceptionListener
{
    public function __construct()
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $previousExceptions = [];
        $exceptionCopy = $exception;
        $type = '';

        while (null !== $exceptionCopy) {
            if (null === $exceptionCopy->getPrevious()) {
                $type = \strrchr($exceptionCopy::class, '\\');
                break;
            }
            $exceptionCopy = $exceptionCopy->getPrevious();
            $previousExceptions[] = $exceptionCopy->getMessage();
        }

        $response = new JsonResponse([
            'errors' => $previousExceptions,
            'type' => \substr(\is_string($type) ? $type : '', 1),
        ], Response::HTTP_BAD_REQUEST);

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
        } else {
            $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}