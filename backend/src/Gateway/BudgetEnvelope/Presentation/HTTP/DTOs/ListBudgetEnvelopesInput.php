<?php

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs;

final readonly class ListBudgetEnvelopesInput
{
    /**
     * @param array<string, string>|null $orderBy
     */
    public function __construct(
        private(set) ?array $orderBy = ['updated_at' => 'DESC'],
        private(set) ?int $limit = 1000,
        private(set) ?int $offset = null,
    ) {
    }
}
