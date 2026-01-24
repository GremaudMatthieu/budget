<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\Controllers;

use App\Presentation\BudgetEnvelope\HTTP\DTOs\ListBudgetEnvelopesInput;
use App\Presentation\BudgetEnvelope\Queries\ListBudgetEnvelopes;
use App\SharedContext\Domain\Ports\Outbound\QueryBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes', name: 'app_budget_envelopes_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListBudgetEnvelopesController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserView $user,
        #[MapQueryString] ListBudgetEnvelopesInput $listBudgetEnvelopesInput = new ListBudgetEnvelopesInput(),
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new ListBudgetEnvelopes(
                    UserId::fromString($user->getUuid()),
                    $listBudgetEnvelopesInput->orderBy,
                    $listBudgetEnvelopesInput->limit,
                    $listBudgetEnvelopesInput->offset,
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
