<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Queries\ListBudgetEnvelopesQuery;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\ListBudgetEnvelopesInput;
use App\SharedContext\Domain\Ports\Outbound\QueryBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
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
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserViewInterface $user,
        #[MapQueryString] ListBudgetEnvelopesInput $listBudgetEnvelopesInput = new ListBudgetEnvelopesInput(),
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new ListBudgetEnvelopesQuery(
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
