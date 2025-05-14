<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\DTOs;

use App\SharedContext\Domain\Enums\ContextEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        private(set) string $uuid,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        private(set) string $budgetPlanUuidThatAlreadyExists,

        #[Assert\NotBlank]
        #[Assert\Type(\DateTimeImmutable::class)]
        private(set) \DateTimeImmutable $date,

        private(set) ?string $context = ContextEnum::BUDGET_PLAN->value,

        private(set) ?string $contextId = null,
    ) {
    }
}
