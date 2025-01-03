<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Ports\Inbound;

interface UserViewInterface
{
    public function getId(): int;

    public function setId(int $id): self;

    public function getUuid(): string;

    public function setUuid(string $uuid): self;

    public function getEmail(): string;

    public function setEmail(string $email): self;

    public function getPassword(): string;

    public function setPassword(string $password): self;

    public function getFirstname(): string;

    public function setFirstname(string $firstname): self;

    public function getLastname(): string;

    public function setLastname(string $lastname): self;

    public function isConsentGiven(): bool;

    public function setConsentGiven(bool $consentGiven): self;

    public function getConsentDate(): \DateTimeImmutable;

    /**
     * @return array<string>
     */
    public function getRoles(): array;

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self;

    public function setConsentDate(\DateTimeImmutable $consentDate): self;

    public function getCreatedAt(): \DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): self;

    public function getUpdatedAt(): \DateTime;

    public function setUpdatedAt(\DateTime $updatedAt): self;

    public function eraseCredentials(): void;

    public function getUserIdentifier(): string;

    public function getPasswordResetToken(): ?string;

    public function setPasswordResetToken(?string $passwordResetToken): self;

    public function getPasswordResetTokenExpiry(): ?\DateTimeImmutable;

    public function setPasswordResetTokenExpiry(?\DateTimeImmutable $passwordResetTokenExpiry): self;

    public static function createFromRepository(array $user): self;
}
