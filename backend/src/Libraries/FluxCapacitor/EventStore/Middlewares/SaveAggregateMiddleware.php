<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Middlewares;

use App\Libraries\FluxCapacitor\EventStore\EventStore;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

#[AutoconfigureTag('messenger.middleware', ['bus' => 'command_bus', 'priority' => 100])]
final readonly class SaveAggregateMiddleware implements MiddlewareInterface
{
    public function __construct(private EventStore $eventStore)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->eventStore->clearTrackedAggregates();

        $envelope = $stack->next()->handle($envelope, $stack);

        $trackedAggregates = $this->eventStore->getTrackedAggregates();

        if (1 === count($trackedAggregates)) {
            $this->eventStore->save($trackedAggregates[0]);
        } elseif (count($trackedAggregates) > 1) {
            $this->eventStore->saveMultiAggregate($trackedAggregates);
        }

        return $envelope;
    }
}
