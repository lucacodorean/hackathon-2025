<?php

namespace App\Validators;

use App\Exception\ExpenseValidationException;
use DateTimeImmutable;

class ExpenseValidator {
    // This validator is in charge of evaluating the requests received from the registration process.
    // Due to the cyclomatic complexity that was previously 12, the logic of the validate method has been
    // parsed in multiple helper-methods.
    /**
     * @param array<string,mixed> $data
     * @param array<string> $allowedCategories
     * @throws ExpenseValidationException
     */
    public function validate(array $data, array $allowedCategories): void
    {
        $errors = [];

        if ($msg = $this->validateDate($data['date'] ?? null)) {
            $errors['date'] = $msg;
        }

        if ($msg = $this->validateCategory($data['category'] ?? null, $allowedCategories)) {
            $errors['category'] = $msg;
        }

        if ($msg = $this->validateAmount($data['amount'] ?? null)) {
            $errors['amount'] = $msg;
        }

        if ($msg = $this->validateDescription($data['description'] ?? null)) {
            $errors['description'] = $msg;
        }

        if (!empty($errors)) {
            throw new ExpenseValidationException($errors);
        }
    }

    private function validateDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return 'Date is required.';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            return 'Date must be in format YYYY-MM-DD HH:MM:SS.';
        }

        if ($date > new DateTimeImmutable()) {
            return 'Date cannot be in the future.';
        }

        return null;
    }

    private function validateCategory(mixed $value, array $allowed): ?string
    {
        if (! is_string($value) || $value === '') {
            return 'Category must be selected.';
        }

        if (! in_array($value, $allowed, true)) {
            return 'Invalid category.';
        }

        return null;
    }

    private function validateAmount(mixed $value): ?string
    {
        if (! is_numeric($value)) {
            return 'Amount must be a number.';
        }

        if ((float) $value <= 0) {
            return 'Amount must be greater than zero.';
        }

        return null;
    }

    private function validateDescription(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return 'Description cannot be empty.';
        }

        return null;
    }
}