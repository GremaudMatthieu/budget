<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeManagement\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}', name: 'app_budget_envelope_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
final readonly class DeleteABudgetEnvelopeController
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($uuid),
            BudgetEnvelopeUserId::fromString($user->getUuid()),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
