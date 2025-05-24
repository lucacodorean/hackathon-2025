<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DI\NotFoundException;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}


    /// All methods follow the same criteria, thus writing the same criteria generation code three wouldn't respect
    /// the reusability principles.
    private function computeParameters(User $user, int $year, int $month): array {
        $startDate = (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0,0,0)
            ->format('Y-m-d H:i:s');

        $endDate = (new \DateTimeImmutable($startDate))
            ->modify('first day of next month')
            ->format('Y-m-d H:i:s');

        return [
            'user_id' => $user->getId(),
            "date >=" => $startDate,
            "date <" => $endDate,
        ];
    }

    public function computeTotalExpenditure(User $user, int $year, int $month): float{
        if(!$user) return 0;

        $criteria = $this->computeParameters($user, $year, $month);
        return $this->expenses->sumAmounts($criteria);
    }

    // This function's purpose is to map the data array to the format required by the frontend.
    private function formatData(array $data, float $total, string $operation): array {
        $out = [];
        foreach ($data as $row) {
            $formattedValue = $row[$operation . '(amount_cents)'] / 100 ;
            $cat    = $row['category'];
            $value  = $formattedValue;
            $percent = $total > 0
                ? round(($formattedValue / $total) * 100, 2)
                : 0.0
            ;

            $out[$cat] = [
                'value'      => $value,
                'percentage' => $percent,
            ];
        }
        return $out;
    }

    // This function will iterate through data resulted by the repository function.
    // It returns the formatted data so the frontend maps it perfectly.
    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        $criteria = $this->computeParameters($user, $year, $month);

        $data = $this->expenses->sumAmountsByCategory($criteria);
        $grandTotal = array_sum(array_column($data, 'SUM(amount_cents)'))/100;

        return $this->formatData($data, $grandTotal, 'SUM');
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user

        $criteria = $this->computeParameters($user, $year, $month);

        $data = $this->expenses->averageAmountsByCategory($criteria);
        $grandTotal = array_sum(array_column($data, 'AVG(amount_cents)'))/100;

        return $this->formatData($data, $grandTotal, 'AVG');
    }
}
