<?php
namespace App\Exception;

class RegisterValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Validation failed on registration.');
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}