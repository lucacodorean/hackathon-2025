<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Exception\InvalidCsrfException;
use App\Exception\UserAlreadyExistsException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password, string $csrf): ?User {

        // I'm using the default password hashing algorithm, which is BCRYPT.
        // I managed to develop exceptions, so the registration process is much cleaner.

        if($this->users->findByUsername($username)) {
            unset($_SESSION['csrf_token']);
            throw new UserAlreadyExistsException("User already exists");
        }

        ///This if statement will evaluate the state of the CSRF token and then remove it from the session in order
        ///to be used one time.
        if(!hash_equals($csrf, $_SESSION['csrf_token'])) {
            unset($_SESSION['csrf_token']);
            throw new InvalidCsrfException("CSRF token mismatch.");
        }

        unset($_SESSION['csrf_token']);
        $user = new User(null, $username, password_hash($password, PASSWORD_DEFAULT), new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool {
        /// The following if statements validate the credentials and the existence of the user in the database.
        $user = $this->users->findByUsername($username);
        if(!$user || !password_verify($password, $user->getPasswordHash())) {
            unset($_SESSION['csrf_token']);
            return false;
        }

        unset($_SESSION['csrf_token']);
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->getId();
        $_SESSION["username"] = $user->getUsername();

        return true;
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    public function retrieveLogged(): ?User {
        return $this->users->findByUsername($_SESSION['username']);
    }
}
