<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\Queries;

use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\Query;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class GetABudgetEnvelopeWithItsLedger implements Query
{
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }

    public function getBudgetEnvelopeUserId(): UserId
    {
        return UserId::fromString($this->budgetEnvelopeUserId);
    }
}
