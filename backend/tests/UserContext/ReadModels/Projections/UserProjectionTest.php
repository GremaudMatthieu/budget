<?php

declare(strict_types=1);

namespace App\Tests\UserContext\ReadModels\Projections;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\UserContext\Domain\Events\UserDeletedDomainEvent_v1;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserReplayedDomainEvent_v1;
use App\UserContext\Domain\Events\UserRewoundDomainEvent_v1;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;
use App\UserContext\Domain\Ports\Inbound\UserOAuthRepositoryInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\ReadModels\Projections\UserProjection;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserProjectionTest extends TestCase
{
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private UserProjection $userProjection;
    private KeyManagementRepositoryInterface&MockObject $keyManagementRepository;
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private UserOAuthRepositoryInterface&MockObject $userOAuthRepository;
    private RefreshTokenManagerInterface&MockObject $refreshTokenManager;

    protected function setUp(): void
    {
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->keyManagementRepository = $this->createMock(KeyManagementRepositoryInterface::class);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->userOAuthRepository = $this->createMock(UserOAuthRepositoryInterface::class);
        $this->userProjection = new UserProjection(
            $this->userViewRepository,
            $this->keyManagementRepository,
            $this->eventEncryptor,
            $this->refreshTokenManager,
            $this->userOAuthRepository,
        );

        $this->eventEncryptor->method('decrypt')->willReturnCallback(
            fn ($event) => $event
        );
    }

    public function testEncryptionKeyDoesNotExist(): void
    {
        $event = new UserSignedUpDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'John',
            'Doe',
            'fr',
            true,
            ['ROLE_USER'],
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserSignedUpEvent(): void
    {
        $event = new UserSignedUpDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'John',
            'Doe',
            'fr',
            true,
            ['ROLE_USER'],
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (UserView $view) => $view->uuid === $event->aggregateId
                    && $view->createdAt == $event->occurredOn
                    && $view->updatedAt == \DateTime::createFromImmutable($event->occurredOn)
                    && $view->email === $event->email
                    && $view->firstname === $event->firstname
                    && $view->lastname === $event->lastname
                    && $view->registrationContext === $event->registrationContext
                    && $view->providerUserId === $event->providerUserId
                    && $view->consentGiven === $event->isConsentGiven
                    && $view->roles === $event->roles));

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEvent(): void
    {
        $event = new UserFirstnameChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            $event->aggregateId,
            'test@mail.com',
            'Test firstName',
            'Test lastName',
            'fr',
            true,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEvent(): void
    {
        $event = new UserLastnameChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'Doe',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            $event->aggregateId,
            'test@mail.com',
            'Test firstName',
            'Test lastName',
            'fr',
            true,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLanguagePreferenceUpdatedEvent(): void
    {
        $event = new UserLanguagePreferenceChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'fr',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            $event->aggregateId,
            'test@mail.com',
            'Test firstName',
            'Test lastName',
            'fr',
            true,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEvent(): void
    {
        $event = new UserDeletedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            $event->aggregateId,
            'test@mail.com',
            'Test firstName',
            'Test lastName',
            'fr',
            true,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('delete')
            ->with($userView);
        $this->userOAuthRepository->expects($this->once())
            ->method('removeOAuthUser')
            ->with($event->aggregateId);
        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserFirstnameChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserLastnameChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'Doe',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLanguagePreferenceUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserLanguagePreferenceChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'fr',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserDeletedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserReplayedEvent(): void
    {
        $event = new UserReplayedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
        );

        $userView = new UserView(
            $event->aggregateId,
            $event->email,
            $event->firstname,
            $event->lastname,
            'fr',
            $event->isConsentGiven,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserReplayedWithUserThatDoesNotExist(): void
    {
        $event = new UserReplayedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserRewoundEvent(): void
    {
        $event = new UserRewoundDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
        );

        $userView = new UserView(
            $event->aggregateId,
            $event->email,
            $event->firstname,
            $event->lastname,
            'fr',
            $event->isConsentGiven,
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserRewoundWithUserThatDoesNotExist(): void
    {
        $event = new UserRewoundDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);

        $this->userProjection->__invoke($event);
    }
}
