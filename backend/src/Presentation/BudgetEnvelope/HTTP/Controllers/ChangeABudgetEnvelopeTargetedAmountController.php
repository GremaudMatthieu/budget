<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\ChangeABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/change-targeted-amount', name: 'app_budget_envelope_change_targeted_amount', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeABudgetEnvelopeTargetedAmountController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] ChangeABudgetEnvelopeTargetedAmountInput $changeABudgetEnvelopeTargetedAmountInput,
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeABudgetEnvelopeTargetedAmount(
                BudgetEnvelopeTargetedAmount::fromString(
                    $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                    $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
                ),
                BudgetEnvelopeId::fromString($uuid),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
