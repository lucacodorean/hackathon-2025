<?php

namespace App\Validators;

use App\Exception\ExpenseValidationException;

class ExpenseValidator {
    // This validator is in charge of evaluating the requests received from the registration process.
    /**
     * @param array<string,mixed> $data
     * @throws ExpenseValidationException
     */
    public function validate(array $data, array $categories): void {
        $errors = [];

        if (empty($data['date'])) {
            $errors['date'] = 'Date is required.';
        } else {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['date']);
            $now  = new \DateTimeImmutable();
            if (! $date) {
                $errors['date'] = 'Date must be in format YYYY-MM-DD HH:MM:SS.';
            } elseif ($date > $now) {
                $errors['date'] = 'Date cannot be in the future.';
            }
        }

        if (empty($data['category'])) {
            $errors['category'] = 'Category must be selected.';
        } else {
            if(!in_array($data['category'], $categories)) {
                $errors['category'] = 'Invalid category.';
            }
        }

        if (! isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Amount must be a number greater than zero.';
        }

        if (empty($data['description']) || trim($data['description']) === '') {
            $errors['description'] = 'Description cannot be empty.';
        }

        if (!empty($errors)) {
            throw new ExpenseValidationException($errors);
        }
    }
}