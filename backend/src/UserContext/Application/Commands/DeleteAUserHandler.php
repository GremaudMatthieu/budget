<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\UserContext\Domain\User;
use App\UserContext\Domain\UserEmailRegistryBuilder;

final readonly class DeleteAUserHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(DeleteAUser $deleteAUser): void
    {
        /** @var User $user */
        $user = $this->eventSourcedRepository->get((string) $deleteAUser->getUserId());
        UserEmailRegistryBuilder::build($this->eventSourcedRepository)
            ->loadOrCreateRegistry()
            ->releaseEmail(
                $user->getEmail(),
                $deleteAUser->getUserId()
            );
        $user->delete($deleteAUser->getUserId());
    }
}
