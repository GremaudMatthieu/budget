<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeManagement\Application\Commands\CreateABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\CreateABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/new', name: 'app_budget_envelope_new', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class CreateABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateABudgetEnvelopeInput $createABudgetEnvelopeInput,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new CreateABudgetEnvelopeCommand(
                $createABudgetEnvelopeInput->getUuid(),
                $user->getUuid(),
                $createABudgetEnvelopeInput->getName(),
                $createABudgetEnvelopeInput->getTargetBudget(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}