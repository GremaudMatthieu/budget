<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;

final readonly class ChangeAUserLastnameCommand implements CommandInterface
{
    private string $userId;
    private string $userLastname;

    public function __construct(
        UserId $userId,
        UserLastname $userLastname,
    ) {
        $this->userId = (string) $userId;
        $this->userLastname = (string) $userLastname;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getUserLastname(): UserLastname
    {
        return UserLastname::fromString($this->userLastname);
    }
}
