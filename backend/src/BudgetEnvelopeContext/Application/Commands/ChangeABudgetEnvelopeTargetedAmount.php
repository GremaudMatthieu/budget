<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ChangeABudgetEnvelopeTargetedAmount implements Command
{
    private string $budgetEnvelopeTargetedAmount;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeTargetedAmount = (string) $budgetEnvelopeTargetedAmount;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeTargetedAmount(): BudgetEnvelopeTargetedAmount
    {
        return BudgetEnvelopeTargetedAmount::fromString($this->budgetEnvelopeTargetedAmount, '0.00');
    }

    public function getBudgetEnvelopeUserId(): UserId
    {
        return UserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }
}
