<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\UserFirstname;

final readonly class ChangeAUserFirstname implements Command
{
    private string $userId;
    private string $userFirstname;

    public function __construct(
        UserId $userId,
        UserFirstname $userFirstname,
    ) {
        $this->userId = (string) $userId;
        $this->userFirstname = (string) $userFirstname;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getFirstname(): UserFirstname
    {
        return UserFirstname::fromString($this->userFirstname);
    }
}
