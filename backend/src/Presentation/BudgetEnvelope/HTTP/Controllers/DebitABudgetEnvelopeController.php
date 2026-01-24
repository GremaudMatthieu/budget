<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\DebitABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/debit', name: 'app_budget_envelope_debit', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class DebitABudgetEnvelopeController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] DebitABudgetEnvelopeInput $debitABudgetEnvelopeInput,
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new DebitABudgetEnvelope(
                BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
                BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
                BudgetEnvelopeId::fromString($uuid),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
