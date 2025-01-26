<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class CreditABudgetEnvelopeCommand implements CommandInterface
{
    private string $budgetEnvelopeCreditMoney;
    private string $budgetEnvelopeEntryDescription;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
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

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }
}
