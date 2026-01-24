<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\UserContext\Domain\User;

final readonly class ReplayAUserEventsHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(ReplayAUserEvents $replayAUserEvents): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $replayAUserEvents->getUserId());
        $aggregate->replay($replayAUserEvents->getUserId());
    }
}
