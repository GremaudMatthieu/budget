<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use App\Presentation\User\UserView;
use App\Presentation\User\TokenIssuer as TokenIssuerInterface;
use App\UserContext\Domain\Exceptions\UserNotFound;
use App\UserContext\JWTTokenManager;
use App\UserContext\UserViewRepository;

final readonly class TokenIssuer implements TokenIssuerInterface
{
    public const int TOKEN_TTL = 2592000;

    public function __construct(
        private UserViewRepository $userViewRepository,
        private JWTTokenManager $jwtManager,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private EntityManager $entityManager,
    ) {
    }

    public function issueTokensForEmail(string $email): array
    {
        $user = $this->userViewRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new UserNotFound('User not found with email: '.$email);
        }

        return $this->generateTokensForUser($user);
    }

    private function generateTokensForUser(UserView $user): array
    {
        $token = $this->jwtManager->create($user);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            self::TOKEN_TTL,
        );

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return [
            'token' => $token,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'uuid' => $user->getUuid(),
                'email' => $user->getEmail(),
                'firstname' => $user->firstname ?? '',
                'lastname' => $user->lastname ?? '',
                'languagePreference' => $user->languagePreference ?? 'en',
            ],
        ];
    }
}
