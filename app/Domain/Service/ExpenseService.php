<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Exception\NotAuthorizedException;
use App\Exception\ResourceNotFoundException;
use App\Exception\UploadedFileInterfaceException;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses

        /// Computing the start date based on the year and month. Then, to get rid of the
        /// issued caused by the 30/31 day months for endDate is used the modify method.
        /// Applying filters based on the user's id and the given dates.

        if(!$user) {
            throw new NotAuthorizedException("User is null.");
        }

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
            $offset, $pageSize
        );
    }

    public function countBy(User $user, int $year, int $month): int {

        if(!$user) {
            throw new NotAuthorizedException("User is null.");
        }

        $startDate = (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0,0,0)
            ->format('Y-m-d H:i:s');

        $endDate = (new \DateTimeImmutable($startDate))
            ->modify('first day of next month')
            ->format('Y-m-d H:i:s');

        return $this->expenses->countBy([
            "user_id" => $user->getId(),
            "date >=" => $startDate,
            "date <=" => $endDate,
        ]);
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

        /// Given the implementation I've followed, that means implementing the validation rules for the expense
        /// the validation happens at controller level. Either way, here we can ensure that the data can be mapped
        /// correctly to the database.
        /// Technically, the validator validates the input, the only parameter that may create problems is
        /// the user, because it may be null.

        if(!$user) {
            throw new NotAuthorizedException("User is null.");
        }

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

        // Given that the save() method implemented in the repository is capable of deciding its purpose.
        // Having the expense sent as a parameter means that the resource may exist, so that means that we may have
        // an id. Thus, we can just validate that the expense is not null. In that case, the expense is a valid entity.

        /// The rest of the validation is handled in the controller.
        /// The issue that we may have is with the amount parameter, which is of type float. Normally, the amount
        /// is an integer value, so that means that we need to make sure that the amount is getting set as an integer.
        /// A possible fix is to consider only the [.] Part of the number, or to convert the entire number (considering)
        /// the floating part to an integer.
        ///
        /// For simplicity, the implemented solution is just handling the conversion.

        if(!$expense) {
            throw new ResourceNotFoundException("Expense not found.");
        }

        $expense->setDate($date);
        $expense->setCategory($category);
        $expense->setDescription($description);
        $expense->setAmountCents(intval($amount));

        $this->expenses->save($expense);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION["flash"] = [
            "type"      => $type,
            "message"   => $message,
        ];
    }
    public function delete(User $user, $expense_id): int {
        $expense = $this->expenses->find($expense_id);
        if(!$expense) {
            $this->setFlash("warning", "Expense not found.");
            return 1;

        }

        if($user->getId() != $expense->getUserId()) {
            $this->setFlash("warning", "Not authorized to delete expense");
            return 1;
        }

        $this->expenses->delete($expense->getId());
        $this->setFlash("success", "Expense deleted.");
        return 0;
    }

    public function listExpenditureYears(User $user): array {
        if(!$user) {
            throw new NotAuthorizedException("User is null.");
        }

        return $this->expenses->listExpenditureYears($user);
    }


    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        // Transactions are easily to be established. For that, all that's needed is to use pdo's beginTransaction()
        // method. In order to keep only unique elements, a set is needed. In the most
        // simplistic way, sets may be implemented as a list that has a visited array attached. Another implementation
        // is to validate the existence of each entry in the array. Both implementations require liniar time.
        // Even so, there is no need of keeping both the entries and the visited list, because we can process them as they
        // come. Thus, the visited list is just enough.
        // This method should call the infrastructure layer that interacts with the database.

        $imported = $this->expenses->importCsv($user, $csvFile);

        if($imported > 0) {
            $this->setFlash("success", "Successfully imported $imported records.");
        }
        else  {
            $this->setFlash("error", "Error at importing the records.");
        }


        return $imported;
    }

    public function find(int $id): ?Expense {
        return $this->expenses->find($id);
    }

    /**
     * This method's purpose is to evaluate the state of a user given an expense.
     * @throws NotAuthorizedException
     */
    public function edit($userId, $expenseId): void {
        if($userId !== $expenseId) {
            throw new NotAuthorizedException("Not authorized to edit expense.");
        }
    }
}
