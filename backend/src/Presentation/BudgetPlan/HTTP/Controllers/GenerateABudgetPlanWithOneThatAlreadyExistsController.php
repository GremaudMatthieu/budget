<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanWithOneThatAlreadyExists;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\HTTP\DTOs\GenerateABudgetPlanWithOneThatAlreadyExistsInput;
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

#[Route('/api/budget-plans-generate-with-one-that-already-exists', name: 'app_budget_plan_generate_with_one_that_already_exists', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] GenerateABudgetPlanWithOneThatAlreadyExistsInput $input,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new GenerateABudgetPlanWithOneThatAlreadyExists(
                BudgetPlanId::fromString($input->uuid),
                BudgetPlanId::fromString(
                    $input->budgetPlanUuidThatAlreadyExists,
                ),
                $input->date,
                UserId::fromString($user->getUuid()),
                null === $input->contextId ?
                    Context::from($input->uuid, ContextEnum::BUDGET_PLAN->value)
                    : Context::from($input->contextId, $input->context),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
