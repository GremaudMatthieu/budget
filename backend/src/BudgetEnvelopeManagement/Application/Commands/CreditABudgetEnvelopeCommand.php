<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Commands;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\CommandInterface;

final readonly class CreditABudgetEnvelopeCommand implements CommandInterface
{
    public function __construct(
        private string $creditMoney,
        private string $uuid,
        private string $userUuid,
    ) {
    }

    public function getCreditMoney(): string
    {
        return $this->creditMoney;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}