<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Presentation\HTTP\Controllers;

use App\EnvelopeManagement\Application\Commands\CreditEnvelopeCommand;
use App\EnvelopeManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\EnvelopeManagement\Presentation\HTTP\DTOs\CreditEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/credit', name: 'app_envelope_credit', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class CreditEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreditEnvelopeInput $creditEnvelopeInput,
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new CreditEnvelopeCommand(
                $creditEnvelopeInput->getCreditMoney(),
                $uuid,
                $user->getUuid(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}