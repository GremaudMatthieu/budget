<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlan;
use App\BudgetPlanContext\Domain\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\BudgetPlanIncome;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\HTTP\DTOs\GenerateABudgetPlanInput;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans-generate', name: 'app_budget_plan_generate', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class GenerateABudgetPlanController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] GenerateABudgetPlanInput $input,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new GenerateABudgetPlan(
                BudgetPlanId::fromString($input->uuid),
                $input->date,
                array_map(
                    fn ($income) => BudgetPlanIncome::fromArray($income),
                    $input->incomes
                ),
                UserId::fromString($user->getUuid()),
                UserLanguagePreference::fromString($user->languagePreference),
                BudgetPlanCurrency::fromString($input->currency),
                null === $input->contextId ?
                    Context::from($input->uuid, ContextEnum::BUDGET_PLAN->value)
                    : Context::from($input->contextId, $input->context),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
