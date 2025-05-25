<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\CategoryBudgetService;
use App\Exception\UploadedFileInterfaceException;
use DateTimeImmutable;
use Exception;
use PDO;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger,
        private readonly CategoryBudgetService $budgetService
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    private function insert($user_id, $date, $category, $amount_cents, $description): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO expenses(id, user_id, date, category, amount_cents, description) 
                   VALUES (:id, :user_id, :date, :category, :amount_cents, :description)"
        );

        $stmt->bindValue(":date",           $date);
        $stmt->bindValue(":category",       $category);
        $stmt->bindValue(":description",    $description);
        $stmt->bindValue(":user_id",        $user_id,       PDO::PARAM_INT);
        $stmt->bindValue(":amount_cents",   $amount_cents);
        $stmt->execute();
    }

    private function update($id, $user_id, $date, $category, $amount_cents, $description): void {
        $stmt = $this->pdo->prepare(
            'UPDATE expenses
                 SET category = :category,
                     description = :description,
                     amount_cents = :amount_cents,
                     user_id = :user_id,
                     date = :date
                 WHERE id = :id'
        );

        $stmt->bindValue(":id",             $id,       PDO::PARAM_INT);
        $stmt->bindValue(":date",           $date);
        $stmt->bindValue(":category",       $category);
        $stmt->bindValue(":description",    $description);
        $stmt->bindValue(":user_id",        $user_id,       PDO::PARAM_INT);
        $stmt->bindValue(":amount_cents",   $amount_cents);
        $stmt->execute();
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.

        // Just like in user's case, the save method is capable of choosing what kind of operation shall do.

        if($expense->getId() === null) {
            $this->insert(
                $expense->getUserId(),
                $expense->getDate()->format('Y-m-d H:i:s'),
                $expense->getCategory(),
                $expense->getAmountCents(),
                $expense->getDescription()
            );
        }

        else {
            $this->update(
                $expense->getId(),
                $expense->getUserId(),
                $expense->getDate()->format('Y-m-d H:i:s'),
                $expense->getCategory(),
                $expense->getAmountCents(),
                $expense->getDescription()
            );
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    /**
     * This function was created to get more reusability of handling the criteria.
     *
     * @param array<string,mixed> $criteria
     * @return array{conds: string[], params: array<string,mixed>}
     */
    private function buildConditions(array $criteria): array
    {
        $index  = 0;
        $conds  = [];
        $params = [];

        foreach ($criteria as $colOp => $val) {

            if (preg_match('/^(.+)\s+(>=|<=|<>|>|<|LIKE)$/i', $colOp, $match)) {
                [, $col, $op] = $match;
            } else {
                $col = $colOp;
                $op  = '=';
            }

            $paramName        = ':' . preg_replace('/\W+/', '_', $colOp) . $index;
            $conds[]          = "`$col` $op $paramName";
            $params[$paramName] = $val;
            $index++;
        }

        return [
            'conds'  => $conds,
            'params' => $params,
        ];
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        // The following implementation should be capable of selecting entries from the database
        // then to apply the criterias on them.
        // The issue I had with the conditions was that when I got to filter based on start-date and end-date,
        // my initial implementation would get date_ field overwritten with the end-date. For that, the params
        // received an indexation based on $index.

        $statement = 'SELECT * FROM expenses';
        $params = [];
        if(!empty($criteria)) {
            list('conds' => $conds, 'params' => $params) = $this->buildConditions($criteria);
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $statement .= ' LIMIT '.$from.', '.$limit;

        /// In this point the statement is formed, and now all that's left is to bind the parameters and execute.

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->execute();

        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(
         function ($row) {
            return $this->createExpenseFromData($row);
        }, $array);
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.

        /// This follows the same implementation steps just like in findBy's case.
        /// To make the process faster we're only counting ids, assuming that the expense may be received
        /// multiple fields in the future and that would make the iteration time per entry longer.

        $statement    = "SELECT COUNT(id) AS cnt FROM expenses";
        $params = [];

        if(!empty($criteria)) {
            list('conds' => $conds, 'params' => $params) = $this->buildConditions($criteria);
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $parameter => $value) {
            $stmt->bindValue($parameter, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT
                CAST(strftime('%Y', date) AS INTEGER) AS year
            FROM expenses
            WHERE user_id = :user_id
            ORDER BY year DESC
        ");

        $stmt->bindValue(":user_id", $user->getId(), PDO::PARAM_INT);
        $stmt->execute();

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), "year");
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $statement    = "SELECT category, SUM(amount_cents)  FROM expenses";
        $params = [];

        if(!empty($criteria)) {
            list('conds' => $conds, 'params' => $params) = $this->buildConditions($criteria);
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $statement .= ' GROUP BY category';

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $parameter => $value) {
            $stmt->bindValue($parameter, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        $statement    = "SELECT category, AVG(amount_cents)  FROM expenses";
        $params = [];

        if(!empty($criteria)) {
            list('conds' => $conds, 'params' => $params) = $this->buildConditions($criteria);
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $statement .= ' GROUP BY category';

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $p => $v) {
            $stmt->bindValue($p, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.

        $statement    = "SELECT SUM(amount_cents) FROM expenses";
        $params = [];

        if(!empty($criteria)) {
            list('conds' => $conds, 'params' => $params) = $this->buildConditions($criteria);
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $p => $v) {
            $stmt->bindValue($p, $v);
        }
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }

    private function validateEntry(array $visited, string $key, int $currentRow, string $category, string $description): bool  {
        /// This if statement invalidates the entries that were already visited.
        if(isset($visited[$key])) {
            $this->logger->warning("Entry at row {currentRow} has been already inserted.", ["currentRow" => $currentRow]);
            return false;
        }

        if($description === "") {
            $this->logger->warning("Entry at row {currentRow} has been already inserted.", ["currentRow" => $currentRow]);
        }

        /// This if statement will validate if the category is valid, according to the .env categories.
        if($this->budgetService->getCategoryBudget($category) === null) {
            $this->logger->warning("Entry at row {currentRow} has an invalid category.", ["currentRow" => $currentRow]);
            return false;
        }

        return true;
    }

    /**
     * This method will handle the logic that encapsulates the insertion of csv's entries in the database.
     * @throws UploadedFileInterfaceException
     */
    public function importCsv(User $user, UploadedFileInterface $file): int {
        // To support the code reusability, the insert() private method can be used in this case to put the
        // correct data in the database.

        $imported = 0;
        $currentRow = 0;
        $visited = [];

        $resource = $file->getStream()->detach();
        if (!is_resource($resource)) {
            $this->logger->error('Error at reading uploaded file.');
            throw new UploadedFileInterfaceException('Error at reading uploaded file.');
        }

        rewind($resource);

        $this->pdo->beginTransaction();
        try {
            while(($currentEntry = fgetcsv($resource)) !== false) {
                $currentRow++;
                /// This if statement will just filter the csv entries that aren't complete.
                if(count($currentEntry) < 4) {
                    $this->logger->warning("Entry at row {currentRow} is not complete.", ["currentRow" => $currentRow]);
                    continue;
                }

                /// This will mass-assign the $currentEntry to the following variables.
                /// To simulate the set, a key will be created based on all variables.
                /// In this way, keeping the key as a string instead of a full object will take less space.
                $key = implode("-", $currentEntry);

                [$date, $amount, $description, $category] = $currentEntry;

                if(!$this->validateEntry($visited, $key, $currentRow, $category, $description)) {
                    continue;
                }

                $date        = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date);
                $amountCents = (int) round((float)$amount * 100);

                $this->insert($user->getId(), $date->format('Y-m-d H:i:s'), $category, $amountCents, $description);
                $visited[$key] = true;
                $imported++;

                $this->logger->info("Entry at row {currentRow} has been imported.", ["currentRow" => $currentRow]);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            $this->logger->error('Error at importing csv file.', ['exception' => $exception->getMessage()]);
            throw new UploadedFileInterfaceException("There has been an error at importing the csv file.");
        }

        return $imported;
    }
}
