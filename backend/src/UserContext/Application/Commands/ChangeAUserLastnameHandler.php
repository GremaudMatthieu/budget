<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\UserContext\Domain\User;

final readonly class ChangeAUserLastnameHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeAUserLastname $command): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getUserId());
        $aggregate->updateLastname($command->getUserLastname(), $command->getUserId());
    }
}
