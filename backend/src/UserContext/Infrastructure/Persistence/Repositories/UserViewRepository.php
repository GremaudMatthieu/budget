<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Persistence\Repositories;

use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\ReadModels\Views\UserView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserViewRepository implements UserViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(UserViewInterface $user): void
    {
        $this->connection->executeStatement('
        INSERT INTO user_view (uuid, created_at, updated_at, email, firstname, lastname, language_preference, consent_given, consent_date, roles, registration_context, provider_user_id)
        VALUES (:uuid, :created_at, :updated_at, :email, :firstname, :lastname, :language_preference, :consent_given, :consent_date, :roles, :registration_context, :provider_user_id)
        ON CONFLICT (uuid) DO UPDATE SET
            updated_at = EXCLUDED.updated_at,
            email = EXCLUDED.email,
            firstname = EXCLUDED.firstname,
            lastname = EXCLUDED.lastname,
            language_preference = EXCLUDED.language_preference,
            consent_given = EXCLUDED.consent_given,
            consent_date = EXCLUDED.consent_date,
            roles = EXCLUDED.roles,
            registration_context = EXCLUDED.registration_context,
            provider_user_id = EXCLUDED.provider_user_id
    ', [
            'uuid' => $user->uuid,
            'created_at' => $user->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $user->updatedAt->format(\DateTime::ATOM),
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'language_preference' => $user->languagePreference,
            'consent_given' => $user->consentGiven,
            'consent_date' => $user->consentDate->format(\DateTimeImmutable::ATOM),
            'roles' => json_encode($user->roles),
            'registration_context' => $user->registrationContext,
            'provider_user_id' => $user->providerUserId,
        ]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(UserViewInterface $user): void
    {
        $this->connection->delete('user_view', ['uuid' => $user->getUuid()]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?UserViewInterface
    {
        $sql = sprintf('SELECT * FROM user_view WHERE %s LIMIT 1', $this->buildWhereClause($criteria));
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        return $result ? UserView::fromRepository($result) : null;
    }

    private function buildWhereClause(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s IS NULL', $key) :
                sprintf('%s = :%s', $key, $key), array_keys($criteria), $criteria),
        );
    }
}
