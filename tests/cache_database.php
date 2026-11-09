<?php

use PHPFramework\Application;
use PHPFramework\Cache;
use PHPFramework\Database;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/helpers/helpers.php';

$dsn = getenv('TEST_DB_DSN');

if (!$dsn) {
    throw new RuntimeException('Set TEST_DB_DSN to a disposable MySQL database.');
}

$connection = new PDO($dsn, getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$connection->exec(
    'CREATE TEMPORARY TABLE cache (
        cache_key VARCHAR(255) PRIMARY KEY,
        data TEXT,
        created_at INT UNSIGNED,
        expires_at INT UNSIGNED
    )'
);

$reflection = new ReflectionClass(Database::class);
$database = $reflection->newInstanceWithoutConstructor();
$connectionProperty = $reflection->getProperty('connection');
$instanceProperty = $reflection->getProperty('instance');

$connectionProperty->setAccessible(true);
$instanceProperty->setAccessible(true);
$connectionProperty->setValue($database, $connection);
$instanceProperty->setValue(null, $database);

$app = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
Application::$app = $app;
$app->db = $database;
$app->cache = new Cache();

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

cache()->set('settings', ['theme' => 'light'], 10);
cache()->set('settings', ['theme' => 'dark'], 100);
check(cache()->get('settings') === ['theme' => 'dark'], 'Cache value was not updated.');
check((int) $connection->query('SELECT COUNT(*) FROM cache')->fetchColumn() === 1, 'Cache update created duplicate entries.');
check((int) $connection->query('SELECT expires_at FROM cache')->fetchColumn() >= time() + 95, 'Expiration was not refreshed.');

cache()->set('expired', 'old', -1);
check(cache()->get('expired', 'fallback') === 'fallback', 'Expired entry was returned.');
cache()->set('expired', 'new');
check(cache()->get('expired') === 'new', 'Expired entry could not be replaced.');

cache()->set('zero', 'value', 0);
check(cache()->get('zero', 'fallback') === 'fallback', 'Zero TTL entry was returned.');
cache()->set('false', false);
check(cache()->get('false', 'fallback') === false, 'False cache value was lost.');
cache()->remove('settings');
check(cache()->get('settings', 'fallback') === 'fallback', 'Removal failed.');
cache()->clear();
check((int) $connection->query('SELECT COUNT(*) FROM cache')->fetchColumn() === 0, 'Clear failed.');

fwrite(STDOUT, 'Database cache tests passed.' . PHP_EOL);
