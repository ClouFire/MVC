<?php

namespace PHPFramework;

final class Config {
    public static string $root;
    public static string $url;
    public static bool $debug;
    public static bool $testLabPhpExecutionEnabled;
    public static bool $testLabSqlConsoleEnabled;
    public static string $config;
    public static string $helpers;
    public static string $app;
    public static string $core;
    public static string $views;
    public static string $layout;
    public static string $path;
    public static string $errorLogs;
    public static string $www;
    public static array $dbSettings;
    public static array $paginationSettings;

    public static function load(string $envFile): void {
        self::loadEnv($envFile);
        self::$root = dirname(__DIR__);
        self::$url = self::env('APP_URL', ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');
        self::$debug = self::booleanEnv('APP_DEBUG', true);
        self::$testLabPhpExecutionEnabled = self::booleanEnv('TEST_LAB_PHP_EXECUTION_ENABLED', false);
        self::$testLabSqlConsoleEnabled = self::booleanEnv('TEST_LAB_SQL_CONSOLE_ENABLED', false);
        self::$config = self::$root . '/config';
        self::$helpers = self::$root . '/helpers';
        self::$app = self::$root . '/app';
        self::$core = self::$root . '/core';
        self::$views = self::$app . '/Views';
        self::$layout = self::env('APP_LAYOUT', 'default');
        self::$path = self::env('APP_PATH', '/');
        self::$errorLogs = self::$root . '/tmp/error.log';
        self::$www = self::$root . '/public';
        $databaseDsn = self::env('DB_DSN', '');
        $databaseName = self::env('DB_DATABASE', '');
        $databaseHost = self::env('DB_HOST', '');
        $databaseDriver = strtolower(self::env('DB_DRIVER', 'mysql'));

        if ($databaseDsn === '') {
            $databaseDsn = self::buildDatabaseDsn($databaseDriver, $databaseHost, $databaseName);
        }

        self::$dbSettings = [
            'dsn' => $databaseDsn,
            'driver' => $databaseDriver,
            'name' => $databaseName,
            'host' => $databaseHost,
            'pass' => self::env('DB_PASSWORD', ''),
            'login' => self::env('DB_USERNAME', ''),
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];

        self::$paginationSettings = [
            'perPage' => self::integerEnv('PAGINATION_PER_PAGE', 2),
            'midSize' => self::integerEnv('PAGINATION_MID_SIZE', 2),
            'maxPages' => self::integerEnv('PAGINATION_MAX_PAGES', 7),
            'tpl' => self::env('PAGINATION_TEMPLATE', 'pagination/base'),
        ];
    }

    private static function buildDatabaseDsn(string $driver, string $host, string $name): string {
        if ($host === '' && $driver !== 'sqlite') {
            throw new \RuntimeException('Missing required environment variable: DB_HOST');
        }

        if ($name === '') {
            throw new \RuntimeException('Missing required environment variable: DB_DATABASE');
        }

        $port = self::env('DB_PORT', match ($driver) {
            'pgsql', 'postgres', 'postgresql', 'postgre' => '5432',
            'sqlsrv' => '1433',
            default => '3306',
        });

        return match ($driver) {
            'mysql' => "mysql:host={$host};port={$port};dbname={$name};charset=" . self::env('DB_CHARSET', 'utf8mb4'),
            'pgsql', 'postgres', 'postgresql', 'postgre' => "pgsql:host={$host};port={$port};dbname={$name}",
            'sqlite' => "sqlite:{$name}",
            'sqlsrv' => "sqlsrv:Server={$host},{$port};Database={$name}",
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}"),
        };
    }

    private static function loadEnv(string $envFile): void {
        if (!is_readable($envFile)) {
            throw new \RuntimeException("Environment file not found: {$envFile}");
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (($value[0] ?? '') === '"' && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (($value[0] ?? '') === "'" && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }

    private static function env(string $name, string $default): string {
        $value = $_ENV[$name] ?? getenv($name);

        return $value === false || $value === '' ? $default : (string) $value;
    }

    private static function booleanEnv(string $name, bool $default): bool {
        return filter_var(self::env($name, $default ? '1' : '0'), FILTER_VALIDATE_BOOL);
    }

    private static function integerEnv(string $name, int $default): int {
        return (int) self::env($name, (string) $default);
    }
}
