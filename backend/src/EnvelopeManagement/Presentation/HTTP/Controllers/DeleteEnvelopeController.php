<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Presentation\HTTP\Controllers;

use App\EnvelopeManagement\Application\Commands\DeleteEnvelopeCommand;
use App\EnvelopeManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}', name: 'app_envelope_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
final readonly class DeleteEnvelopeController
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(new DeleteEnvelopeCommand($uuid, $user->getUuid()));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
