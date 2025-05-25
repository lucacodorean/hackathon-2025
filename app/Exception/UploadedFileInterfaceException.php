<?php

namespace App\Exception;

class UploadedFileInterfaceException extends \Exception
{
    public function __construct(string $message) {
        parent::__construct($message);
    }
}