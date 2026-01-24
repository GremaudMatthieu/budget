<?php

declare(strict_types=1);

namespace App\Presentation\User;

interface TokenIssuer
{
    public function issueTokensForEmail(string $email): array;
}
