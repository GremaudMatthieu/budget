<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\Queries;

use App\SharedContext\Domain\Ports\Inbound\Query;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ListBudgetEnvelopes implements Query
{
    private string $budgetEnvelopeUserId;

    public function __construct(
        UserId $budgetEnvelopeUserId,
        private ?array $orderBy = null,
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeUserId(): UserId
    {
        return UserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }
}
