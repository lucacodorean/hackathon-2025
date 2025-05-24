<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
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
        $stmt->bindValue(":amount_cents",   $amount_cents,  PDO::PARAM_INT);
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
        $stmt->bindValue(":amount_cents",   $amount_cents,  PDO::PARAM_INT);
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

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        // The following implementation should be capable of selecting entries from the database
        // then to apply the criterias on them.
        // The issue I had with the conditions was that when I got to filter based on start-date and end-date,
        // my initial implementation would get date_ field overwritten with the end-date. For that, the params
        // received an indexation based on $i.

        $statement = 'SELECT * FROM expenses';

        $i = 0;
        if(!empty($criteria)) {
            $conds = [];
            foreach ($criteria as $colOp => $val) {

                /// This part of the code will (hopefully) handle the comparison operations.
                if (preg_match('/^(.+)\s+(>=|<=|<>|>|<|LIKE)$/i', $colOp, $m)) {
                    [, $col, $op] = $m;
                } else {
                    $col = $colOp;
                    $op  = '=';
                }

                $param    = ':' . preg_replace('/\W+/', '_', $colOp) . $i;
                $conds[]  = "`$col` $op $param";
                $params[$param] = $val;
                $i++;
            }
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }


        $statement .= ' LIMIT '.$from.', '.$limit;

        /// In this point the statement is formed, and now all that's left is to bind the parameters and execute.

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.

        /// This follows the same implementation steps just like in findBy's case.
        /// To make the process faster we're only counting ids, assuming that the expense may be received
        /// multiple fields in the future and that would make the iteration time per entry longer.

        $statement    = "SELECT COUNT(id) AS cnt FROM expenses";
        $params = [];

        $i = 0;
        if(!empty($criteria)) {
            $conds = [];
            foreach ($criteria as $colOp => $val) {

                /// This part of the code will (hopefully) handle the comparison operations.
                if (preg_match('/^(.+)\s+(>=|<=|<>|>|<|LIKE)$/i', $colOp, $m)) {
                    [, $col, $op] = $m;
                } else {
                    $col = $colOp;
                    $op  = '=';
                }

                $param    = ':' . preg_replace('/\W+/', '_', $colOp) . $i;
                $conds[]  = "`$col` $op $param";
                $params[$param] = $val;
                $i++;
            }
            $statement .= ' WHERE ' . implode(' AND ', $conds);
        }

        $stmt = $this->pdo->prepare($statement);
        foreach ($params as $p => $v) {
            $stmt->bindValue($p, $v);
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
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
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
}
