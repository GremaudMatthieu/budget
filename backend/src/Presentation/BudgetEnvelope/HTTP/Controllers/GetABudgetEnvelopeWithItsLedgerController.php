<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\Controllers;

use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\Queries\GetABudgetEnvelopeWithItsLedger;
use App\SharedContext\Domain\Ports\Outbound\QueryBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
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
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetABudgetEnvelopeWithItsLedger(
                    BudgetEnvelopeId::fromString($uuid),
                    UserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
