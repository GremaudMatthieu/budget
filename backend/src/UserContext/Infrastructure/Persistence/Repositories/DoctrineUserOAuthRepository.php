<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Persistence\Repositories;

use App\UserContext\Domain\UserOAuthRepository;
use App\UserContext\Infrastructure\Entities\UserOAuth;
use Doctrine\ORM\EntityManager;

final readonly class DoctrineUserOAuthRepository implements UserOAuthRepository
{
    public function __construct(
        private EntityManager $entityManager,
    ) {
    }

    public function findOneBy(array $criteria): ?UserOAuth
    {
        return $this->entityManager->getRepository(UserOAuth::class)->findOneBy($criteria);
    }

    public function createOAuthLink(string $userId, string $provider, string $providerUserId): void
    {
        $existingLink = $this->findOneBy([
            'userId' => $userId,
            'provider' => $provider,
        ]);

        if ($existingLink) {
            return;
        }

        $oauthLink = new UserOAuth($userId, $provider, $providerUserId);
        $this->entityManager->persist($oauthLink);
        $this->entityManager->flush();
    }

    public function removeOAuthUser(string $userId): void
    {
        $oauthLink = $this->findOneBy([
            'userId' => $userId,
        ]);

        if ($oauthLink) {
            $this->entityManager->remove($oauthLink);
            $this->entityManager->flush();
        }
    }
}
