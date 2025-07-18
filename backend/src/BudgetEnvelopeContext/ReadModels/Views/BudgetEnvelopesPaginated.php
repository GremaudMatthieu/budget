<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopesPaginatedInterface;

final class BudgetEnvelopesPaginated implements BudgetEnvelopesPaginatedInterface, \jsonSerializable
{
    /* @var array<object> */
    public private(set) iterable $budgetEnvelopes;
    public private(set) int $totalItems;

    /**
     * @param array<object> $budgetEnvelopes
     */
    public function __construct(iterable $budgetEnvelopes, int $totalItems)
    {
        $this->budgetEnvelopes = $budgetEnvelopes;
        $this->totalItems = $totalItems;
    }

    public function jsonSerialize(): array
    {
        return [
            'envelopes' => $this->budgetEnvelopes,
            'totalItems' => $this->totalItems,
        ];
    }
}
