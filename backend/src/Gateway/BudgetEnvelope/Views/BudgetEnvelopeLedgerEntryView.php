<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_ledger_entry_view')]
#[ORM\Index(name: 'idx_budget_envelope_ledger_entry_view_budget_envelope_uuid', columns: ['budget_envelope_uuid'])]
class BudgetEnvelopeLedgerEntryView implements BudgetEnvelopeLedgerEntryViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_envelope_ledger_view_id_seq', allocationSize: 1, initialValue: 1)]
    public int $id;

    #[ORM\Column(name: 'budget_envelope_uuid', type: 'string', length: 36, unique: false)]
    public string $budgetEnvelopeUuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'monetary_amount', type: 'string', length: 13)]
    public string $monetaryAmount;

    #[ORM\Column(name: 'entry_type', type: 'string', length: 6)]
    public string $entryType;

    #[ORM\Column(name: 'description', type: 'string', length: 13, options: ['default' => ''])]
    public string $description;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    public string $userUuid;

    private function __construct(
        string $budgetEnvelopeId,
        string $entryType,
        string $budgetEnvelopeEntryDescription,
        string $budgetEnvelopeUserId,
        \DateTimeImmutable $createdAt,
        string $monetaryAmount,
    ) {
        $this->budgetEnvelopeUuid = $budgetEnvelopeId;
        $this->entryType = $entryType;
        $this->description = $budgetEnvelopeEntryDescription;
        $this->userUuid = $budgetEnvelopeUserId;
        $this->createdAt = $createdAt;
        $this->monetaryAmount = $monetaryAmount;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelopeLedgerEntry): self
    {
        return new self(
            $budgetEnvelopeLedgerEntry['aggregate_id'],
            $budgetEnvelopeLedgerEntry['entry_type'],
            $budgetEnvelopeLedgerEntry['description'],
            $budgetEnvelopeLedgerEntry['user_uuid'],
            new \DateTimeImmutable($budgetEnvelopeLedgerEntry['created_at']),
            $budgetEnvelopeLedgerEntry['monetary_amount'],
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            BudgetEnvelopeEntryType::CREDIT,
            $event->description,
            $event->userId,
            $event->occurredOn,
            $event->creditMoney,
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            BudgetEnvelopeEntryType::DEBIT,
            $event->description,
            $event->userId,
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
