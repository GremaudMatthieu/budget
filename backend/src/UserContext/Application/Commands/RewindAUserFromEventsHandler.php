<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\UserContext\Domain\User;

final readonly class RewindAUserFromEventsHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(RewindAUserFromEvents $command): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getUserId(), $command->getDesiredDateTime());
        $aggregate->rewind($command->getUserId());
    }
}
