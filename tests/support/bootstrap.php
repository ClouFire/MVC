<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

function configureTestApplication(): void {
    \PHPFramework\Config::$path = '/';
    \PHPFramework\Config::$views = dirname(__DIR__, 2) . '/app/Views';
    \PHPFramework\Config::$www = dirname(__DIR__, 2) . '/public';
    \PHPFramework\Config::$layout = 'default';
    \PHPFramework\Config::$testLabPhpExecutionEnabled = false;
    \PHPFramework\Config::$testLabSqlConsoleEnabled = false;
    \PHPFramework\Config::$paginationSettings = [
        'perPage' => 2,
        'midSize' => 2,
        'maxPages' => 7,
        'tpl' => 'pagination/base',
    ];
}

function configureTestDatabase(): void {
    $dsn = getenv('TEST_DB_DSN');

    if (!$dsn) {
        throw new RuntimeException('TEST_DB_DSN is required.');
    }

    \PHPFramework\Config::$dbSettings = [
        'dsn' => $dsn,
        'login' => getenv('TEST_DB_USERNAME') ?: 'root',
        'pass' => getenv('TEST_DB_PASSWORD') ?: '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ];
}
