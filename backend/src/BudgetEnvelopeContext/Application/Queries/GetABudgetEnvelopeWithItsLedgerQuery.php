<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Queries;

use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\QueryInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class GetABudgetEnvelopeWithItsLedgerQuery implements QueryInterface
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
