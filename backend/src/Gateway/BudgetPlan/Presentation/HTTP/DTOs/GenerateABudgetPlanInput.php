<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GenerateABudgetPlanInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        private(set) string $uuid,

        #[Assert\NotBlank]
        #[Assert\Type(\DateTimeImmutable::class)]
        private(set) \DateTimeImmutable $date,

        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\All([
            new Assert\Collection([
                'fields' => [
                    'uuid' => new Assert\Required([
                        new Assert\NotBlank(),
                        new Assert\Uuid(),
                        new Assert\Regex(
                            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                        ),
                    ]),
                    'incomeName' => new Assert\Required([
                        new Assert\NotBlank(),
                    ]),
                    'amount' => new Assert\Required([
                        new Assert\NotBlank(),
                        new Assert\Type('string'),
                        new Assert\Regex(
                            pattern: '/^\d+(\.\d{2})?$/',
                            message: 'incomes.amountInvalid'
                        ),
                    ]),
                ],
                'allowExtraFields' => false,
            ]),
        ])]
        private(set) array $incomes,
    ) {
    }
}
