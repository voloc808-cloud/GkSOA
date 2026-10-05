<?php

// Check connectivity before Artisan makes any schema changes. Never print credentials.
try {
    $app = require __DIR__.'/../backend/bootstrap/app.php';
    $app['db']->connection()->getPdo();
    echo "Database connection OK.\n";
} catch (Throwable $exception) {
    $current = $exception;
    $driverCode = null;
    do {
        if ($current instanceof PDOException && isset($current->errorInfo[1])) {
            $driverCode = (int) $current->errorInfo[1];
            break;
        }
        $current = $current->getPrevious();
    } while ($current);

    $message = match ($driverCode) {
        1045 => 'MySQL rejected the login (1045). Check DB_USERNAME and DB_PASSWORD in backend/.env and any DB_* environment overrides. Use the credentials of your local MySQL server.',
        1049 => 'MySQL database does not exist (1049). Create the database named by DB_DATABASE in backend/.env before running migrations.',
        1044 => 'MySQL denied database access (1044). Grant the configured user access to the database named by DB_DATABASE.',
        2002, 2003 => 'Cannot reach MySQL. Start MySQL and check DB_HOST and DB_PORT in backend/.env.',
        default => 'Database connection failed. Check the PDO driver, database configuration and service availability.',
    };
    fwrite(STDERR, $message."\n");
    exit(1);
}
