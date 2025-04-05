<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;

interface UserViewInterface
{
    public static function fromRepository(array $user): self;

    public static function fromUserSignedUpDomainEvent(UserSignedUpDomainEvent $event): self;

    public function fromEvents(\Generator $events): void;

    public function fromEvent(DomainEventInterface $event): void;

    public function getPassword(): ?string;

    public function getUuid(): string;

    public function getEmail(): string;

    public function getRoles(): array;

    public function eraseCredentials(): void;

    public function getUserIdentifier(): string;

    public function jsonSerialize(): array;
}
