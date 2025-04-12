<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Services;

use App\UserContext\Domain\Exceptions\UserNotFoundException;
use App\UserContext\Domain\Ports\Inbound\AuthenticationServiceInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\EntityManagerInterface;
use App\UserContext\Domain\Ports\Outbound\JWTTokenManagerInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenGeneratorInterface;
use App\UserContext\ReadModels\Views\UserView;

final readonly class AuthenticationService implements AuthenticationServiceInterface
{
    public const int TOKEN_TTL = 2592000;

    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function generateTokensForUser(UserView $user): array
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
            ]
        ];
    }

    public function authenticateByEmail(string $email): array
    {
        $user = $this->userViewRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new UserNotFoundException('User not found with email: ' . $email);
        }

        return $this->generateTokensForUser($user);
    }
}
