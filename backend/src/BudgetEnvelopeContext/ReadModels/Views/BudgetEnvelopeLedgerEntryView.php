<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryType;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_ledger_entry_view')]
#[ORM\Index(name: 'idx_budget_envelope_ledger_entry_view_budget_envelope_uuid', columns: ['budget_envelope_uuid'])]
final class BudgetEnvelopeLedgerEntryView implements BudgetEnvelopeLedgerEntryViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_envelope_ledger_view_id_seq', allocationSize: 1, initialValue: 1)]
    private(set) int $id;

    #[ORM\Column(name: 'budget_envelope_uuid', type: 'string', length: 36, unique: false)]
    private(set) string $budgetEnvelopeUuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'monetary_amount', type: 'string', length: 13)]
    private(set) string $monetaryAmount;

    #[ORM\Column(name: 'entry_type', type: 'string', length: 6)]
    private(set) string $entryType;

    #[ORM\Column(name: 'description', type: 'string', length: 13, options: ['default' => ''])]
    private(set) string $description;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userUuid;

    private function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeEntryType $entryType,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        \DateTimeImmutable $createdAt,
        string $monetaryAmount,
    ) {
        $this->budgetEnvelopeUuid = (string) $budgetEnvelopeId;
        $this->entryType = (string) $entryType;
        $this->description = (string) $budgetEnvelopeEntryDescription;
        $this->userUuid = (string) $budgetEnvelopeUserId;
        $this->createdAt = $createdAt;
        $this->monetaryAmount = $monetaryAmount;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelopeLedgerEntry): self
    {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelopeLedgerEntry['aggregate_id']),
            BudgetEnvelopeEntryType::fromString($budgetEnvelopeLedgerEntry['entry_type']),
            BudgetEnvelopeEntryDescription::fromString($budgetEnvelopeLedgerEntry['description']),
            BudgetEnvelopeUserId::fromString($budgetEnvelopeLedgerEntry['user_uuid']),
            new \DateTimeImmutable($budgetEnvelopeLedgerEntry['created_at']),
            $budgetEnvelopeLedgerEntry['monetary_amount'],
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeCreditedDomainEvent(BudgetEnvelopeCreditedDomainEvent $event): self
    {
        return new self(
            BudgetEnvelopeId::fromString($event->aggregateId),
            BudgetEnvelopeEntryType::fromString(BudgetEnvelopeEntryType::CREDIT),
            BudgetEnvelopeEntryDescription::fromString($event->description),
            BudgetEnvelopeUserId::fromString($event->userId),
            $event->occurredOn,
            $event->creditMoney,
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeDebitedDomainEvent(BudgetEnvelopeDebitedDomainEvent $event): self {
        return new self(
            BudgetEnvelopeId::fromString($event->aggregateId),
            BudgetEnvelopeEntryType::fromString(BudgetEnvelopeEntryType::DEBIT),
            BudgetEnvelopeEntryDescription::fromString($event->description),
            BudgetEnvelopeUserId::fromString($event->userId),
            $event->occurredOn,
            $event->debitMoney,
        );
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => $this->monetaryAmount,
            'entry_type' => $this->entryType,
            'description' => $this->description,
        ];
    }
}
