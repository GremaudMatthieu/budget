<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;

final readonly class SignUpAUserCommand implements CommandInterface
{
    private string $userId;
    private string $userEmail;
    private string $userFirstname;
    private string $userLastname;
    private string $userLanguagePreference;
    private bool $userConsentGiven;
    private string $userRegistrationContext;
    private string $providerUserId;

    public function __construct(
        UserId $userId,
        UserEmail $userEmail,
        UserFirstname $userFirstname,
        UserLastname $userLastname,
        UserLanguagePreference $userLanguagePreference,
        UserConsent $userConsentGiven,
        UserRegistrationContext $userRegistrationContext,
        string $providerUserId,
    ) {
        $this->userId = (string) $userId;
        $this->userEmail = (string) $userEmail;
        $this->userFirstname = (string) $userFirstname;
        $this->userLastname = (string) $userLastname;
        $this->userLanguagePreference = (string) $userLanguagePreference;
        $this->userConsentGiven = $userConsentGiven->toBool();
        $this->userRegistrationContext = (string) $userRegistrationContext;
        $this->providerUserId = $providerUserId;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getUserEmail(): UserEmail
    {
        return UserEmail::fromString($this->userEmail);
    }

    public function getUserFirstname(): UserFirstname
    {
        return UserFirstname::fromString($this->userFirstname);
    }

    public function getUserLastname(): UserLastname
    {
        return UserLastname::fromString($this->userLastname);
    }

    public function getUserLanguagePreference(): UserLanguagePreference
    {
        return UserLanguagePreference::fromString($this->userLanguagePreference);
    }

    public function isUserConsentGiven(): UserConsent
    {
        return UserConsent::fromBool($this->userConsentGiven);
    }

    public function getUserRegistrationContext(): UserRegistrationContext
    {
        return UserRegistrationContext::fromString($this->userRegistrationContext);
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }
}
