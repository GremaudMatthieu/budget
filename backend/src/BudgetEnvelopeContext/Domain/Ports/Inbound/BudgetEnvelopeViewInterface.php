<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface BudgetEnvelopeViewInterface
{
    public static function fromRepository(array $budgetEnvelope): self;

    public static function fromBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $event,
    ): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function jsonSerialize(): array;
}
