<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class AddABudgetEnvelope implements Command
{
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;
    private string $budgetEnvelopeName;
    private string $budgetEnvelopeTargetedAmount;
    private string $budgetEnvelopeCurrency;
    private string $context;
    private string $contextId;

    public function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeCurrency $budgetEnvelopeCurrency,
        Context $context,
    ) {
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
        $this->budgetEnvelopeName = (string) $budgetEnvelopeName;
        $this->budgetEnvelopeTargetedAmount = (string) $budgetEnvelopeTargetedAmount;
        $this->budgetEnvelopeCurrency = (string) $budgetEnvelopeCurrency;
        $this->context = $context->getContext();
        $this->contextId = $context->getContextId();
    }

    public function getBudgetEnvelopeUserId(): UserId
    {
        return UserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }

    public function getBudgetEnvelopeName(): BudgetEnvelopeName
    {
        return BudgetEnvelopeName::fromString($this->budgetEnvelopeName);
    }

    public function getBudgetEnvelopeTargetedAmount(): BudgetEnvelopeTargetedAmount
    {
        return BudgetEnvelopeTargetedAmount::fromString($this->budgetEnvelopeTargetedAmount, '0.00');
    }

    public function getBudgetEnvelopeCurrency(): BudgetEnvelopeCurrency
    {
        return BudgetEnvelopeCurrency::fromString($this->budgetEnvelopeCurrency);
    }

    public function getContext(): Context
    {
        return Context::from($this->contextId, $this->context);
    }
}
