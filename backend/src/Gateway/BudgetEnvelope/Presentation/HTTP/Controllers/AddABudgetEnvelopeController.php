<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
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
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetEnvelopeInput $addABudgetEnvelopeInput,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetEnvelopeCommand(
                BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
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
