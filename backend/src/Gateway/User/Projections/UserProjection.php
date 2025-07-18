<?php

declare(strict_types=1);

namespace App\Gateway\User\Projections;

use App\Gateway\User\Views\UserView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\UserContext\Domain\Events\UserDeletedDomainEvent_v1;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserReplayedDomainEvent_v1;
use App\UserContext\Domain\Events\UserRewoundDomainEvent_v1;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;
use App\UserContext\Domain\Ports\Inbound\UserOAuthRepositoryInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UserProjection
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private KeyManagementRepositoryInterface $keyManagementRepository,
        private EventEncryptorInterface $eventEncryptor,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private UserOAuthRepositoryInterface $userOAuthRepository,
    ) {
    }

    public function __invoke(UserDomainEventInterface $event): void
    {
        $encryptionKey = $this->keyManagementRepository->getKey($event->aggregateId);

        if (!$encryptionKey) {
            return;
        }

        $event = $this->eventEncryptor->decrypt($event, $event->aggregateId);

        match($event::class) {
            UserSignedUpDomainEvent_v1::class => $this->handleUserSignedUpDomainEvent_v1($event),
            UserFirstnameChangedDomainEvent_v1::class => $this->handleUserFirstnameChangedDomainEvent_v1($event),
            UserLastnameChangedDomainEvent_v1::class => $this->handleUserLastnameChangedDomainEvent_v1($event),
            UserLanguagePreferenceChangedDomainEvent_v1::class => $this->handleUserLanguagePreferenceChangedDomainEvent_v1($event),
            UserDeletedDomainEvent_v1::class => $this->handleUserDeletedDomainEvent_v1($event),
            UserReplayedDomainEvent_v1::class => $this->handleUserReplayedDomainEvent_v1($event),
            UserRewoundDomainEvent_v1::class => $this->handleUserRewoundDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleUserSignedUpDomainEvent_v1(UserSignedUpDomainEvent_v1 $event): void
    {
        $this->userViewRepository->save(UserView::fromUserSignedUpDomainEvent_v1($event));
    }

    private function handleUserFirstnameChangedDomainEvent_v1(UserFirstnameChangedDomainEvent_v1 $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
    }

    private function handleUserLanguagePreferenceChangedDomainEvent_v1(
        UserLanguagePreferenceChangedDomainEvent_v1 $event,
    ): void {
        $userView = $this->userViewRepository->findOneBy([
            'uuid' => $event->aggregateId,
        ]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
    }

    private function handleUserLastnameChangedDomainEvent_v1(UserLastnameChangedDomainEvent_v1 $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
    }

    private function handleUserDeletedDomainEvent_v1(UserDeletedDomainEvent_v1 $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $this->userViewRepository->delete($userView);
        $this->keyManagementRepository->deleteKey($event->aggregateId);
        $this->refreshTokenManager->deleteAll($userView->getEmail());
        $this->userOAuthRepository->removeOAuthUser($event->aggregateId);
    }

    private function handleUserReplayedDomainEvent_v1(UserReplayedDomainEvent_v1 $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
    }

    private function handleUserRewoundDomainEvent_v1(UserRewoundDomainEvent_v1 $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
    }
}
