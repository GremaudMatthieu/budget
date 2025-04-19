<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\EventListener;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Services\RequestIdProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestIdListener implements EventSubscriberInterface
{
    private RequestIdProvider $requestIdProvider;

    public function __construct(RequestIdProvider $requestIdProvider)
    {
        $this->requestIdProvider = $requestIdProvider;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->requestIdProvider->requestId = $event->getRequest()->headers->get(
            'request-id',
            DomainEventInterface::DEFAULT_REQUEST_ID,
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
