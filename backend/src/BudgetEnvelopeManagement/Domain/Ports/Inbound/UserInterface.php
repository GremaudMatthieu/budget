<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Inbound;

interface UserInterface
{
    public function getUuid(): string;

    public function getEmail(): string;
}