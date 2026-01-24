<?php

declare(strict_types=1);

namespace App\SharedContext;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;

interface UserView
{
    public static function fromRepository(array $user): self;

    public static function fromUserSignedUpDomainEvent_v1(UserSignedUpDomainEvent_v1 $event): self;

    public function fromEvents(\Generator $events): void;

    public function fromEvent(DomainEvent $event): void;

    public function getUuid(): string;

    public function getEmail(): string;

    public function getRoles(): array;

    public function eraseCredentials(): void;

    public function getUserIdentifier(): string;

    public function jsonSerialize(): array;
}
