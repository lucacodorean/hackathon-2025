<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Exception\UserAlreadyExistsException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): ?User
    {
        // TODO: check that a user with same username does not exist, create new user and persist
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with

        // I'm using the default password hashing algorithm which is BCRYPT.
        // I managed to develop exceptions so the registration process is much cleaner.

        if($this->users->findByUsername($username)) {
            throw new UserAlreadyExistsException("User already exists");
        }

        $user = new User(null, $username, password_hash($password, PASSWORD_DEFAULT), new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sur ethe user exists and the password matches
        // TODO: don't forget to store in session user data needed afterwards

        /// The following if statements validates the credentials and the existence of the user in the database.
        $user = $this->users->findByUsername($username);
        if(!$user) {
            return false;
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->getId();
        $_SESSION["username"] = $user->getUsername();

        return true;
    }
}
