<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;

interface BudgetEnvelopeLedgerEntry
{
    public static function fromRepository(array $budgetEnvelopeLedgerEntry): self;

    public function jsonSerialize(): array;

    public static function fromBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): self;

    public static function fromBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): self;
}
