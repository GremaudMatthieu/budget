<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class DebitABudgetEnvelope implements Command
{
    private string $budgetEnvelopeDebitMoney;
    private string $budgetEnvelopeEntryDescription;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeDebitMoney = (string) $budgetEnvelopeDebitMoney;
        $this->budgetEnvelopeEntryDescription = (string) $budgetEnvelopeEntryDescription;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeDebitMoney(): BudgetEnvelopeDebitMoney
    {
        return BudgetEnvelopeDebitMoney::fromString($this->budgetEnvelopeDebitMoney);
    }

    public function getBudgetEnvelopeEntryDescription(): BudgetEnvelopeEntryDescription
    {
        return BudgetEnvelopeEntryDescription::fromString($this->budgetEnvelopeEntryDescription);
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
