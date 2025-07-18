<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Persistence\Repositories;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopesPaginatedInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopesPaginated;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class BudgetEnvelopeViewRepository implements BudgetEnvelopeViewRepositoryInterface
{
    private Connection $connection;
    private const array BOOLEAN_FIELDS = ['is_deleted'];
    private const string TABLE_NAME = 'budget_envelope_view';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(BudgetEnvelopeViewInterface $budgetEnvelope): void
    {
        $this->connection->executeStatement(
            'INSERT INTO '.self::TABLE_NAME.' (
                uuid, created_at, updated_at, current_amount, targeted_amount, 
                name, user_uuid, currency, is_deleted, context_uuid, context
            ) VALUES (
                :uuid, :created_at, :updated_at, :current_amount, :targeted_amount,
                :name, :user_uuid, :currency, :is_deleted, :context_uuid, :context
            ) ON CONFLICT (uuid) DO UPDATE SET
                updated_at = EXCLUDED.updated_at,
                current_amount = EXCLUDED.current_amount,
                targeted_amount = EXCLUDED.targeted_amount,
                name = EXCLUDED.name,
                user_uuid = EXCLUDED.user_uuid,
                currency = EXCLUDED.currency,
                is_deleted = EXCLUDED.is_deleted,
                context_uuid = EXCLUDED.context_uuid,
                context = EXCLUDED.context',
            $this->prepareDataForSave($budgetEnvelope)
        );
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(BudgetEnvelopeViewInterface $budgetEnvelope): void
    {
        $this->connection->delete(self::TABLE_NAME, ['uuid' => $budgetEnvelope->uuid]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetEnvelopeViewInterface
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->setMaxResults(1);

        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);

        $result = $qb->executeQuery()->fetchAssociative();

        return $result ? BudgetEnvelopeView::fromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneEnvelopeWithItsLedgerBy(array $criteria, ?array $orderBy = null): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select(
            'ev.uuid',
            'ev.created_at',
            'ev.updated_at',
            'ev.current_amount',
            'ev.targeted_amount',
            'ev.name',
            'ev.user_uuid',
            'ev.is_deleted',
            'ev.currency',
            'ev.context_uuid',
            'ev.context',
            'ehv.budget_envelope_uuid',
            'ehv.created_at AS ledger_created_at',
            'ehv.monetary_amount',
            'ehv.entry_type',
            'ehv.description'
        )
            ->from(self::TABLE_NAME, 'ev')
            ->leftJoin('ev', 'budget_envelope_ledger_entry_view', 'ehv', 'ev.uuid = ehv.budget_envelope_uuid')
            ->orderBy('ehv.created_at', 'DESC');

        $this->addWhereClausesWithAlias($qb, $criteria, 'ev');

        $result = $qb->executeQuery()->fetchAllAssociative();

        if (!$result) {
            return [];
        }

        $budgetEnvelopeData = $result[0];
        unset(
            $budgetEnvelopeData['budget_envelope_uuid'],
            $budgetEnvelopeData['monetary_amount'],
            $budgetEnvelopeData['entry_type'],
            $budgetEnvelopeData['description'],
            $budgetEnvelopeData['ledger_created_at'],
        );

        return [
            'envelope' => BudgetEnvelopeView::fromRepository($budgetEnvelopeData),
            'ledger' => null !== $result[0]['ledger_created_at'] ? array_map(
                fn ($row) => $this->mapToBudgetEnvelopeLedgerView([
                    'aggregate_id' => $row['budget_envelope_uuid'],
                    'user_uuid' => $row['user_uuid'],
                    'created_at' => $row['ledger_created_at'],
                    'monetary_amount' => $row['monetary_amount'],
                    'entry_type' => $row['entry_type'],
                    'description' => $row['description'],
                ]),
                $result
            ) : [],
        ];
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): BudgetEnvelopesPaginatedInterface {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME);

        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        $result = $qb->executeQuery();

        return new BudgetEnvelopesPaginated(
            array_map([$this, 'mapToBudgetEnvelopeView'], $result->fetchAllAssociative()),
            $result->rowCount()
        );
    }

    private function prepareDataForSave(BudgetEnvelopeViewInterface $budgetEnvelope): array
    {
        return [
            'uuid' => $budgetEnvelope->uuid,
            'created_at' => $budgetEnvelope->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetEnvelope->updatedAt->format(\DateTime::ATOM),
            'current_amount' => $budgetEnvelope->currentAmount,
            'targeted_amount' => $budgetEnvelope->targetedAmount,
            'name' => $budgetEnvelope->name,
            'user_uuid' => $budgetEnvelope->userUuid,
            'currency' => $budgetEnvelope->currency,
            'is_deleted' => $budgetEnvelope->isDeleted ? '1' : '0',
            'context' => $budgetEnvelope->context,
            'context_uuid' => $budgetEnvelope->contextUuid,
        ];
    }

    private function addWhereClauses(QueryBuilder $qb, array $criteria): void
    {
        $processedCriteria = $this->processCriteria($criteria);

        foreach ($processedCriteria as $field => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull($field));
            } else {
                $qb->andWhere($qb->expr()->eq($field, ":$field"));
                $qb->setParameter($field, $value);
            }
        }
    }

    private function addWhereClausesWithAlias(QueryBuilder $qb, array $criteria, string $alias): void
    {
        $processedCriteria = $this->processCriteria($criteria);

        foreach ($processedCriteria as $field => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull("$alias.$field"));
            } else {
                $qb->andWhere($qb->expr()->eq("$alias.$field", ":$field"));
                $qb->setParameter($field, $value);
            }
        }
    }

    private function addOrderByClauses(QueryBuilder $qb, ?array $orderBy): void
    {
        if (!$orderBy) {
            return;
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
    }

    private function processCriteria(array $criteria): array
    {
        $processed = [];

        foreach ($criteria as $field => $value) {
            if (null === $value) {
                continue;
            }

            if (in_array($field, self::BOOLEAN_FIELDS, true)) {
                $processed[$field] = is_bool($value) ? ($value ? '1' : '0') : '0';
            } else {
                $processed[$field] = $value;
            }
        }

        if (!isset($processed['is_deleted']) && !isset($criteria['is_deleted'])) {
            $processed['is_deleted'] = '0';
        }

        return $processed;
    }

    private function mapToBudgetEnvelopeView(array $data): BudgetEnvelopeViewInterface
    {
        return BudgetEnvelopeView::fromRepository($data);
    }

    private function mapToBudgetEnvelopeLedgerView(array $data): BudgetEnvelopeLedgerEntryViewInterface
    {
        return BudgetEnvelopeLedgerEntryView::fromRepository($data);
    }
}
