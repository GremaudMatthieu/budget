<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class CreditABudgetEnvelope implements Command
{
    private string $budgetEnvelopeCreditMoney;
    private string $budgetEnvelopeEntryDescription;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeCreditMoney = (string) $budgetEnvelopeCreditMoney;
        $this->budgetEnvelopeEntryDescription = (string) $budgetEnvelopeEntryDescription;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeCreditMoney(): BudgetEnvelopeCreditMoney
    {
        return BudgetEnvelopeCreditMoney::fromString($this->budgetEnvelopeCreditMoney);
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
