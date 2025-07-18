<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\CreditABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/credit', name: 'app_budget_envelope_credit', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class CreditABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreditABudgetEnvelopeInput $creditABudgetEnvelopeInput,
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new CreditABudgetEnvelopeCommand(
                BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
                BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
                BudgetEnvelopeId::fromString($uuid),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
