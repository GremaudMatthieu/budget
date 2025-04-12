<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\HTTP\Controllers;

use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Application\Commands\SignUpOrAuthenticateWithOAuth2Command;
use App\UserContext\Domain\Ports\Inbound\AuthenticationServiceInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SignUpOrAuthenticateAGoogleUserController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly CommandBusInterface $commandBus,
        private readonly UuidGeneratorInterface $uuidGenerator,
        private readonly AuthenticationServiceInterface $authenticationService,
    ) {
    }

    #[Route('/api/connect/google', name: 'connect_google')]
    public function connect(): RedirectResponse
    {
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(
                ['email', 'profile'],
                []
            );
    }

    #[Route('/api/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): Response
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->clientRegistry->getClient('google')->fetchUser();
        $email = $googleUser->getEmail();
        $this->commandBus->execute(
            new SignUpOrAuthenticateWithOAuth2Command(
                UserId::fromString($this->uuidGenerator->generate()),
                UserEmail::fromString($googleUser->getEmail()),
                UserFirstname::fromString($googleUser->getFirstName() ?? 'N/A'),
                UserLastname::fromString($googleUser->getLastName() ?? 'N/A'),
                UserLanguagePreference::fromString($googleUser->getLocale() ?? 'en'),
                UserConsent::fromBool(true),
                UserRegistrationContext::fromString('google'),
                $googleUser->getId(),
            )
        );
        $authResult = $this->authenticationService->authenticateByEmail($email);

        return $this->redirect($this->buildRedirectUrl($email, $authResult['token'], $authResult['refresh_token']));
    }

    private function buildRedirectUrl(string $email, string $token, ?string $refreshToken = null): string
    {
        $frontendUrl = $this->getParameter('app.frontend_url') ?? 'http://localhost:3000';
        
        $url = "{$frontendUrl}/oauth/google/callback?email=" . urlencode($email) .
            "&token=" . urlencode($token);
        
        if ($refreshToken) {
            $url .= "&refresh_token=" . urlencode($refreshToken);
        }
        
        return $url;
    }
}
