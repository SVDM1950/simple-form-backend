<?php

use App\Application;
use App\Exception\StopException;
use Dotenv\Dotenv;

// error handling
set_error_handler(
    /** @throws ErrorException */
    fn($errorNumber, $errorMessage, $errorFilename, $errorLine) => throw new ErrorException($errorMessage, $errorNumber, 0, $errorFilename, $errorLine)
);

chdir(__DIR__ . '/..');
require 'vendor/autoload.php';

(Dotenv::createImmutable(__DIR__ . '/..', ['.env', '.env.local'], false))->safeLoad();

try {
    $app = new Application('config');
    $app->run();
} catch (StopException $exception) {
    // do nothing
}
