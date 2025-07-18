<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Queries\GetABudgetEnvelopeWithItsLedgerQuery;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Outbound\QueryBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}', name: 'app_budget_envelope_get_one_with_its_ledger', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class GetABudgetEnvelopeWithItsLedgerController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetABudgetEnvelopeWithItsLedgerQuery(
                    BudgetEnvelopeId::fromString($uuid),
                    UserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
