<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Repositories;

use App\UserContext\Domain\Entities\UserOAuth;
use App\UserContext\Domain\Ports\Inbound\UserOAuthRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserOAuthRepository implements UserOAuthRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
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
