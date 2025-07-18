<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;

final readonly class ChangeAUserLanguagePreferenceCommand implements CommandInterface
{
    private string $userId;
    private string $userLanguagePreference;

    public function __construct(
        UserId $userId,
        UserLanguagePreference $userLanguagePreference,
    ) {
        $this->userId = (string) $userId;
        $this->userLanguagePreference = (string) $userLanguagePreference;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getLanguagePreference(): UserLanguagePreference
    {
        return UserLanguagePreference::fromString($this->userLanguagePreference);
    }
}
