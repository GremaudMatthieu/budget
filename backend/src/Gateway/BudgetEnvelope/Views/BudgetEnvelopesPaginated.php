<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Views;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopesPaginatedInterface;

class BudgetEnvelopesPaginated implements BudgetEnvelopesPaginatedInterface, \jsonSerializable
{
    /* @var array<object> */
    public iterable $budgetEnvelopes;
    public int $totalItems;

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
