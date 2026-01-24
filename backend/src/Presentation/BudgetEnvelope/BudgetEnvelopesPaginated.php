<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope;

interface BudgetEnvelopesPaginated
{
    public function jsonSerialize(): array;
}
