<?php

namespace App\EnvelopeManagement\ReadModels\Views;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'envelope_view')]
#[ORM\Index(name: 'idx_envelope_view_user_uuid', columns: ['user_uuid'])]
final class EnvelopeView implements EnvelopeViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(name: 'current_budget', type: 'string', length: 13)]
    private string $currentBudget;

    #[ORM\Column(name: 'target_budget', type: 'string', length: 13)]
    private string $targetBudget;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private string $userUuid;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private bool $isDeleted;

    public function __construct(
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[\Override]
    public static function createFromRepository(array $envelope): self
    {
        return (new self())->setCurrentBudget($envelope['current_budget'])
            ->setTargetBudget($envelope['target_budget'])
            ->setName($envelope['name'])
            ->setIsDeleted($envelope['is_deleted'])
            ->setCreatedAt(new \DateTimeImmutable($envelope['created_at']))
            ->setUpdatedAt(new \DateTime($envelope['updated_at']))
            ->setTargetBudget($envelope['target_budget'])
            ->setUuid($envelope['uuid'])
            ->setUserUuid($envelope['user_uuid'])
        ;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    #[\Override]
    public function getUuid(): string
    {
        return $this->uuid;
    }

    #[\Override]
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    #[\Override]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[\Override]
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[\Override]
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    #[\Override]
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[\Override]
    public function getTargetBudget(): string
    {
        return $this->targetBudget;
    }

    #[\Override]
    public function setTargetBudget(string $targetBudget): self
    {
        $this->targetBudget = $targetBudget;

        return $this;
    }

    #[\Override]
    public function getCurrentBudget(): string
    {
        return $this->currentBudget;
    }

    #[\Override]
    public function setCurrentBudget(string $currentBudget): self
    {
        $this->currentBudget = $currentBudget;

        return $this;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[\Override]
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    #[\Override]
    public function setUserUuid(string $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    #[\Override]
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    #[\Override]
    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'currentBudget' => $this->currentBudget,
            'targetBudget' => $this->targetBudget,
            'name' => $this->name,
        ];
    }
}
