<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\ListBudgetPlansCalendarQuery;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class ListBudgetPlansCalendarQueryHandler
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
    ) {
    }

    public function __invoke(ListBudgetPlansCalendarQuery $query): array
    {
        return $this->generateCalendarFromBudgetPlans(
            $this->budgetPlanViewRepository->findBy(
                [
                    'user_uuid' => (string) $query->getBudgetPlanUserId(),
                    'is_deleted' => false,
                ],
            ),
        );
    }

    private function generateCalendarFromBudgetPlans(array $budgetPlans): array
    {
        return array_reduce($budgetPlans, function (array $calendarStructure, array $plan) {
            $date = new \DateTimeImmutable($plan['date']);
            $year = (int)$date->format('Y');
            $month = (int)$date->format('n');

            if (!isset($calendarStructure[$year])) {
                $calendarStructure[$year] = [];
            }

            $calendarStructure[$year][$month] = $plan['uuid'];

            return $calendarStructure;
        }, []);
    }
}
