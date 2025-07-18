<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWantCategory;
use App\Gateway\BudgetPlan\Presentation\HTTP\DTOs\AddABudgetPlanWantInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}/add-want', name: 'app_budget_plan_add_want', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AddABudgetPlanWantController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetPlanWantInput $addABudgetPlanWantInput,
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetPlanWantCommand(
                BudgetPlanId::fromString($uuid),
                BudgetPlanEntryId::fromString($addABudgetPlanWantInput->uuid),
                BudgetPlanEntryName::fromString($addABudgetPlanWantInput->name),
                BudgetPlanEntryAmount::fromString($addABudgetPlanWantInput->amount),
                BudgetPlanWantCategory::fromString($addABudgetPlanWantInput->category),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
