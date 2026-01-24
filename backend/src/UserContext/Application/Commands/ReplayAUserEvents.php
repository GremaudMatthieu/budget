<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ReplayAUserEvents implements Command
{
    private string $userId;

    public function __construct(
        UserId $userId,
    ) {
        $this->userId = (string) $userId;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }
}
