<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, created_at 
             FROM users 
             WHERE username = :u 
             LIMIT 1'
        );
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new User(
            (int)$row['id'],
            $row['username'],
            $row['password_hash'],
            new \DateTimeImmutable($row['created_at'])
        );
    }

    private function registerUser($username, $password, $createdAt): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (id, username, password_hash, created_at) 
                   VALUES (:id, :username, :password_hash, :created_at)"
        );

        $stmt->bindValue(":username",   $username, PDO::PARAM_STR);
        $stmt->bindValue(":password_hash", $password, PDO::PARAM_STR);
        $stmt->bindValue(":created_at", $createdAt,PDO::PARAM_STR);
        $stmt->execute();
    }

    private function updateUser($id, $username, $password): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users
                 SET username = :username,
                     password_hash = :password_hash
                 WHERE id = :id'
        );
        $stmt->bindValue(":username",   $username, PDO::PARAM_STR);
        $stmt->bindValue(":password",   $password, PDO::PARAM_STR);
        $stmt->bindValue(':id',         $id,       PDO::PARAM_INT);
        $stmt->execute();
    }
    public function save(User $user): void
    {
        // TODO: Implement save() method.
        /// This function should be just a decider between which kind of operation the save is.
        /// Based on the user's id state, the save may be an insertion (used in registration process)
        /// Or it can be an update.

        if($user->getId() === null) {
            $this->registerUser(
                $user->getUsername(),
                $user->getPasswordHash(),
                $user->getCreatedAt()->format('Y-m-d H:i:s'));
        }
        else {
            $this->updateUser($user->getId(), $user->getUsername(), $user->getPasswordHash());
        }
    }

    public function getExpenses(User $user): array {
        // This method collects the expenses that a user has stored in the database.

        $stmt = $this->pdo->prepare(
            'SELECT * FROM expenses where user_id = :id order by date desc'
        );

        $stmt->execute(['id' => $user->getId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
