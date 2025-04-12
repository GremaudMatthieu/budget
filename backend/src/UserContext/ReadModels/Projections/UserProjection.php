<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Projections;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\Infrastructure\Events\Notifications\UserDeletedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserFirstnameChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserLanguagePreferenceChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserLastnameChangedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserReplayedNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserRewoundNotificationEvent;
use App\UserContext\Infrastructure\Events\Notifications\UserSignedUpNotificationEvent;
use App\UserContext\ReadModels\Views\UserView;

final readonly class UserProjection
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private KeyManagementRepositoryInterface $keyManagementRepository,
        private EventEncryptorInterface $eventEncryptor,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private PublisherInterface $publisher,
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
            UserSignedUpDomainEvent::class => $this->handleUserSignedUpDomainEvent($event),
            UserFirstnameChangedDomainEvent::class => $this->handleUserFirstnameChangedDomainEvent($event),
            UserLastnameChangedDomainEvent::class => $this->handleUserLastnameChangedDomainEvent($event),
            UserLanguagePreferenceChangedDomainEvent::class => $this->handleUserLanguagePreferenceChangedDomainEvent($event),
            UserDeletedDomainEvent::class => $this->handleUserDeletedDomainEvent($event),
            UserReplayedDomainEvent::class => $this->handleUserReplayedDomainEvent($event),
            UserRewoundDomainEvent::class => $this->handleUserRewoundDomainEvent($event),
            default => null,
        };
    }

    private function handleUserSignedUpDomainEvent(UserSignedUpDomainEvent $event): void
    {
        $this->userViewRepository->save(UserView::fromUserSignedUpDomainEvent($event));
        try {
            $this->publisher->publishNotificationEvents([UserSignedUpNotificationEvent::fromDomainEvent($event)]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserFirstnameChangedDomainEvent(UserFirstnameChangedDomainEvent $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserFirstnameChangedNotificationEvent::fromDomainEvent(
                    $event,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserLanguagePreferenceChangedDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $event,
    ): void {
        $userView = $this->userViewRepository->findOneBy([
            'uuid' => $event->aggregateId
        ]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserLanguagePreferenceChangedNotificationEvent::fromDomainEvent(
                    $event,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserLastnameChangedDomainEvent(UserLastnameChangedDomainEvent $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([
                UserLastnameChangedNotificationEvent::fromDomainEvent(
                    $event,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserDeletedDomainEvent(UserDeletedDomainEvent $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $this->userViewRepository->delete($userView);
        $this->keyManagementRepository->deleteKey($event->aggregateId);
        $this->refreshTokenManager->deleteAll($userView->getEmail());
        try {
            $this->publisher->publishNotificationEvents([UserDeletedNotificationEvent::fromDomainEvent($event)]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserReplayedDomainEvent(UserReplayedDomainEvent $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([UserReplayedNotificationEvent::fromDomainEvent($event)]);
        } catch (\Exception $e) {
        }
    }

    private function handleUserRewoundDomainEvent(UserRewoundDomainEvent $event): void
    {
        $userView = $this->userViewRepository->findOneBy(['uuid' => $event->aggregateId]);

        if (!$userView instanceof UserViewInterface) {
            return;
        }

        $userView->fromEvent($event);
        $this->userViewRepository->save($userView);
        try {
            $this->publisher->publishNotificationEvents([UserRewoundNotificationEvent::fromDomainEvent($event)]);
        } catch (\Exception $e) {
        }
    }
}
