<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class RenameABudgetEnvelopeCommand implements CommandInterface
{
    private string $budgetEnvelopeName;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeName = (string) $budgetEnvelopeName;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeName(): BudgetEnvelopeName
    {
        return BudgetEnvelopeName::fromString($this->budgetEnvelopeName);
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
