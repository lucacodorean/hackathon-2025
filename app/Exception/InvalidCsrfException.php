<?php

namespace App\Exception;

class InvalidCsrfException extends \Exception
{
    public function __construct(string $message) {
        parent::__construct($message);
    }
}