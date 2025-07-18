<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface BudgetEnvelopeViewInterface
{
    public static function fromRepository(array $budgetEnvelope): self;

    public static function fromBudgetEnvelopeAddedDomainEvent_v1(
        BudgetEnvelopeAddedDomainEvent_v1 $event,
    ): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function jsonSerialize(): array;
}
