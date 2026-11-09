<?php

use PHPFramework\Config;
use PHPFramework\Migrator;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);

    exit;
}

require __DIR__ . '/vendor/autoload.php';

try {
    Config::load(__DIR__ . '/.env');

    $connection = new PDO(
        Config::$dbSettings['dsn'],
        Config::$dbSettings['login'],
        Config::$dbSettings['pass'],
        Config::$dbSettings['options']
    );

    $migrator = new Migrator($connection, __DIR__ . '/database/migrations');
    $completed = $migrator->migrate();

    foreach ($completed as $migration) {
        fwrite(STDOUT, "Applied: {$migration}" . PHP_EOL);
    }

    if ($completed === []) {
        fwrite(STDOUT, 'No pending migrations.' . PHP_EOL);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);

    if ($exception->getPrevious() instanceof RuntimeException && !$exception->getPrevious() instanceof PDOException) {
        fwrite(STDERR, $exception->getPrevious()->getMessage() . PHP_EOL);
    }

    exit(1);
}
