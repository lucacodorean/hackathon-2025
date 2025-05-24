<?php

namespace App\Validators;

use App\Exception\RegisterValidationException;

class RegisterValidator
{
    // This validator is in charge of evalating the requests received from the registration process.
    /**
     * @throws RegisterValidationException
     */
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['username']) || mb_strlen($data['username']) < 4) {
            $errors['username'] = 'Username should be at least 4 characters long';
        }

        if (empty($data['password']) || mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Password should have at least 8 characters long';
        } elseif (!preg_match('/\d/', $data['password'])) {
            $errors['password'] = 'Password should contain at least a digit.';
        }

        if (!isset($data['password_confirm']) || $data['password_confirm'] !== $data['password']) {
            $errors['password_confirm'] = 'Passwords don\'t match.';
        }

        if (!empty($errors)) {
            throw new RegisterValidationException($errors);
        }
    }
}