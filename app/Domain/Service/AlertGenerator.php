<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.

    public function __construct(
        private readonly CategoryBudgetService $budgetService,
        private readonly MonthlySummaryService $summaryService,
    ) { }


    // Normally the $budgets and $totalForMonth arrays would share the same keys, given that they are both
    // formatted to work with months as keys.
    public function generate(User $user, int $year, int $month): array
    {
        $alerts = [];
        $budgets = $this->budgetService->getBudgets();
        $totalForMonth = $this->summaryService->computePerCategoryTotals($user, $year, $month);

        foreach($budgets as $category => $value) {
            if(array_key_exists($category, $totalForMonth)) {
                if($totalForMonth[$category]["value"] > $value && $totalForMonth[$category]["value"] > 0) {
                    $alerts[$category] = $totalForMonth[$category]["value"] - $value;
                }
            }
        }

        return $alerts;
    }
}
