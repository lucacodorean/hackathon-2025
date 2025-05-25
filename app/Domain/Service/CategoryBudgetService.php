<?php

declare(strict_types=1);

namespace App\Domain\Service;

class CategoryBudgetService
{
    private array $categoryBudgets;

    public function __construct(string $categoryBudgetsJson) {
        $this->categoryBudgets = json_decode($categoryBudgetsJson, true);
    }

    public function getBudgets(): array {
        return $this->categoryBudgets;
    }

    public function getCategoryBudget(string $category): ?float {
        return $this->categoryBudgets[$category] ?? null;
    }
}
