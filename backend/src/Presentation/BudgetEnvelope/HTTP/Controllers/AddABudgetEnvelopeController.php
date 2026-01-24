<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/add', name: 'app_budget_envelope_add', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AddABudgetEnvelopeController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetEnvelopeInput $addABudgetEnvelopeInput,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetEnvelope(
                BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                UserId::fromString($user->getUuid()),
                BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
                BudgetEnvelopeTargetedAmount::fromString(
                    $addABudgetEnvelopeInput->targetedAmount,
                    '0.00',
                ),
                BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
                null === $addABudgetEnvelopeInput->contextId ?
                    Context::from($addABudgetEnvelopeInput->uuid, ContextEnum::BUDGET_ENVELOPE->value)
                    : Context::from($addABudgetEnvelopeInput->contextId, $addABudgetEnvelopeInput->context),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
