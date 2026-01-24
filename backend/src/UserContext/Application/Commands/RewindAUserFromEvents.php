<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class RewindAUserFromEvents implements Command
{
    private string $userId;
    private string $desiredDateTime;

    public function __construct(
        UserId $userId,
        \DateTimeImmutable $desiredDateTime,
    ) {
        $this->userId = (string) $userId;
        $this->desiredDateTime = $desiredDateTime->format(\DateTimeInterface::ATOM);
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getDesiredDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->desiredDateTime);
    }
}
