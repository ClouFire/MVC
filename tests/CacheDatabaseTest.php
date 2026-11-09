<?php

use PHPFramework\Application;
use PHPFramework\Cache;
use PHPFramework\Database;

final class CacheDatabaseTest extends DatabaseTestCase {
    protected function setUp(): void {
        parent::setUp();

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
    }

    public function testReplacementAndExpirationRefresh(): void {
        $connection = Database::connection();

        cache()->set('settings', ['theme' => 'light'], 10);
        cache()->set('settings', ['theme' => 'dark'], 100);
        self::assertTrue(cache()->get('settings') === ['theme' => 'dark'], 'Cache value was not updated.');
        self::assertTrue((int) $connection->query('SELECT COUNT(*) FROM cache')->fetchColumn() === 1, 'Cache update created duplicate entries.');
        self::assertTrue((int) $connection->query('SELECT expires_at FROM cache')->fetchColumn() >= time() + 95, 'Expiration was not refreshed.');
    }

    public function testExpiration(): void {
        $connection = Database::connection();

        cache()->set('expired', 'old', -1);
        self::assertTrue(cache()->get('expired', 'fallback') === 'fallback', 'Expired entry was returned.');
        cache()->set('expired', 'new');
        self::assertTrue(cache()->get('expired') === 'new', 'Expired entry could not be replaced.');

        cache()->set('zero', 'value', 0);
        self::assertTrue(cache()->get('zero', 'fallback') === 'fallback', 'Zero TTL entry was returned.');
    }

    public function testFalseValues(): void {
        $connection = Database::connection();

        cache()->set('false', false);
        self::assertTrue(cache()->get('false', 'fallback') === false, 'False cache value was lost.');
    }

    public function testRemovalAndClear(): void {
        $connection = Database::connection();

        cache()->set('settings', 'value');

        cache()->remove('settings');
        self::assertTrue(cache()->get('settings', 'fallback') === 'fallback', 'Removal failed.');
        cache()->clear();
        self::assertTrue((int) $connection->query('SELECT COUNT(*) FROM cache')->fetchColumn() === 0, 'Clear failed.');
    }
}
