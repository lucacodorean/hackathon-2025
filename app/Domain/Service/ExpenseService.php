<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses

        /// Computing the start date based on the year and month. Then, in order to get rid of the
        /// issued caused by the 30/31 day months for endDate is used the modify method.
        /// Applying filters based on user's id and the given dates.

        $startDate = (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0,0,0)
            ->format('Y-m-d H:i:s');

        $endDate = (new \DateTimeImmutable($startDate))
            ->modify('first day of next month')
            ->format('Y-m-d H:i:s');


        $offset = ($pageNumber - 1) * $pageSize;

        return $this->expenses->findBy([
            "user_id" => $user->getId(),
            "date >=" => $startDate,
            "date <=" => $endDate,
            ],
            $offset, $pageSize);
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->getId(), $date, $category, (int)$amount, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
