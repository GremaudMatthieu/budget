<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\Presentation\User\TokenIssuer;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Application\Commands\SignUpOrAuthenticateWithOAuth2;
use App\UserContext\Domain\UserConsent;
use App\UserContext\Domain\UserEmail;
use App\UserContext\Domain\UserFirstname;
use App\UserContext\Domain\UserLastname;
use App\UserContext\Domain\UserRegistrationContext;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SignUpOrAuthenticateAGoogleUserController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly CommandBus $commandBus,
        private readonly UuidGenerator $uuidGenerator,
        private readonly TokenIssuer $tokenIssuer,
    ) {
    }

    #[Route('/api/connect/google', name: 'connect_google')]
    public function connect(Request $request): RedirectResponse
    {
        $platform = $request->query->get('platform', 'web');
        $redirectUri = $request->query->get('redirect_uri');
        $state = $request->query->get('state');
        $request->getSession()->set('oauth_platform', $platform);
        if ($redirectUri) {
            $request->getSession()->set('oauth_redirect_uri', $redirectUri);
        }

        $params = ['state' => $state ?: json_encode(['platform' => $platform])];
        if ($redirectUri && 'mobile' === $platform) {
            $params['redirect_uri'] = $redirectUri;
        }

        return $this->clientRegistry
            ->getClient('google')
            ->redirect(
                ['email', 'profile'],
                $params
            );
    }

    #[Route('/api/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(Request $request): Response
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->clientRegistry->getClient('google')->fetchUser();
        $email = $googleUser->getEmail();
        $state = $request->query->get('state');
        $platform = 'web';
        $languagePreference = null;

        if ($state) {
            $stateData = json_decode($state, true);
            if (isset($stateData['platform'])) {
                $platform = $stateData['platform'];
            }
            if (isset($stateData['languagePreference'])) {
                $languagePreference = $stateData['languagePreference'];
            }
        } else {
            $platform = $request->getSession()->get('oauth_platform', 'web');
        }

        $redirectUri = $request->getSession()->get('oauth_redirect_uri');
        $mobileRedirectUri = $request->getSession()->get('oauth_mobile_redirect');

        if ('mobile' === $platform && $mobileRedirectUri) {
            $redirectUri = $mobileRedirectUri;
        }

        $uuid = $this->uuidGenerator->generate();

        $this->commandBus->execute(
            new SignUpOrAuthenticateWithOAuth2(
                UserId::fromString($uuid),
                UserEmail::fromString($googleUser->getEmail()),
                UserFirstname::fromString($googleUser->getFirstName() ?? 'N/A'),
                UserLastname::fromString($googleUser->getLastName() ?? 'N/A'),
                UserLanguagePreference::fromString($languagePreference ?? 'en'),
                UserConsent::fromBool(true),
                UserRegistrationContext::fromString('google'),
                $googleUser->getId(),
                Context::from($uuid, ContextEnum::USER->value),
            )
        );
        $authResult = $this->tokenIssuer->issueTokensForEmail($email);

        return $this->redirect($this->buildRedirectUrl($email, $authResult['token'], $platform, $redirectUri, $authResult['refresh_token']));
    }

    private function buildRedirectUrl(string $email, string $token, string $platform = 'web', ?string $customRedirectUri = null, ?string $refreshToken = null): string
    {
        if ('mobile' === $platform) {
            // Use the app scheme for mobile deep linking
            $baseUrl = $customRedirectUri ?? $this->getParameter('MOBILE_APP_SCHEME');
            $separator = (str_contains($baseUrl, '?')) ? '&' : '?';
            $url = $baseUrl.$separator.'email='.urlencode($email).'&token='.urlencode($token);
        } else {
            // Hardcode the frontend URL for now to fix the localhost issue
            $frontendUrl = 'https://gogobudgeto.expo.app';
            $url = "{$frontendUrl}/oauth/google/callback?email=".urlencode($email).
                '&token='.urlencode($token);
        }

        if ($refreshToken) {
            $url .= '&refresh_token='.urlencode($refreshToken);
        }

        return $url;
    }
}
