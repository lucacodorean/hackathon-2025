<?php

namespace App\Exception;

class NotAuthorizedException extends \Exception
{
    public function __construct(string $message) {
        parent::__construct($message);
    }
}