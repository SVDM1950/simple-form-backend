<?php

use App\Application;
use Dotenv\Dotenv;

// error handling
set_error_handler(
    /** @throws ErrorException */
    fn($errorNumber, $errorMessage, $errorFilename, $errorLine) => throw new ErrorException($errorMessage, $errorNumber, 0, $errorFilename, $errorLine)
);

chdir(__DIR__ . '/..');
require 'vendor/autoload.php';

try {
    (Dotenv::createImmutable(__DIR__ . '/../'))->safeLoad();
} catch (ErrorException $e) {
}

$app = new Application('config');
$app->run();
