<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEvent;

interface BudgetEnvelope
{
    public static function fromRepository(array $budgetEnvelope): self;

    public static function fromBudgetEnvelopeAddedDomainEvent_v1(
        BudgetEnvelopeAddedDomainEvent_v1 $event,
    ): self;

    public function fromEvent(DomainEvent $event): void;

    public function jsonSerialize(): array;
}
