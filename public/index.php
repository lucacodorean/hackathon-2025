<?php

declare(strict_types=1);

// initializing class autoloader
require __DIR__.'/../vendor/autoload.php';

use App\Kernel;
use Dotenv\Dotenv;

/// This if statement makes sure to enable a session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// loading .env file variables
$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

// creating and running web application
$app = Kernel::createApp();
$app->run();
