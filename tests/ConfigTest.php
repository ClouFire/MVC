<?php

use PHPFramework\Config;

final class ConfigTest extends FrameworkTestCase {
    public function testEnvironmentConfiguration(): void {
        $file = tempnam(sys_get_temp_dir(), 'mvc-config-');

        try {
            file_put_contents($file, <<<'ENV'
        # ignored
        APP_DEBUG=false
        APP_URL="example.test/"
        APP_LAYOUT='custom'
        APP_PATH=/portal/
        TEST_LAB_PHP_EXECUTION_ENABLED=0
        TEST_LAB_SQL_CONSOLE_ENABLED=1
        DB_DRIVER=mysql
        DB_HOST=database
        DB_DATABASE=example
        DB_DSN=
        DB_PORT=3307
        DB_CHARSET=utf8mb4
        DB_USERNAME=tester
        DB_PASSWORD="value=with=equals"
        ADMIN_NAME=" Administrator "
        ADMIN_EMAIL=" ADMIN@EXAMPLE.TEST "
        ADMIN_PASSWORD=' spaced password '
        PAGINATION_PER_PAGE=15
        PAGINATION_MID_SIZE=3
        PAGINATION_MAX_PAGES=9
        PAGINATION_TEMPLATE=pagination/base
        invalid line
        ENV);
            Config::load($file);

            self::assertTrue(!Config::$debug, 'Boolean debug flag was not parsed.');
            self::assertTrue(!Config::$testLabPhpExecutionEnabled && Config::$testLabSqlConsoleEnabled, 'Console flags are incorrect.');
            self::assertTrue(Config::$url === 'example.test/' && Config::$layout === 'custom', 'Quoted configuration was not parsed.');
            self::assertTrue(Config::$dbSettings['dsn'] === 'mysql:host=database;port=3307;dbname=example;charset=utf8mb4', 'MySQL DSN is incorrect.');
            self::assertTrue(Config::$dbSettings['pass'] === 'value=with=equals', 'Environment values were split incorrectly.');
            self::assertTrue(Config::$adminCredentials === [
                'name' => 'Administrator',
                'email' => 'admin@example.test',
                'password' => ' spaced password ',
            ], 'Admin credentials were not normalized correctly.');
            self::assertTrue(Config::$paginationSettings['perPage'] === 15, 'Integer setting was not parsed.');
            self::assertTrue(is_dir(Config::$views) && is_dir(Config::$www), 'Project paths are incorrect.');

            foreach ([
                ['pgsql', '5432', 'pgsql:host=database;port=5432;dbname=example'],
                ['sqlite', '', 'sqlite:example'],
                ['sqlsrv', '1433', 'sqlsrv:Server=database,1433;Database=example'],
            ] as [$driver, $port, $expected]) {
                file_put_contents($file, "DB_DRIVER={$driver}\nDB_PORT={$port}\nDB_DSN=\n");
                Config::load($file);
                self::assertTrue(Config::$dbSettings['dsn'] === $expected, 'Driver DSN is incorrect: ' . $driver);
            }

            file_put_contents($file, "DB_DSN=mysql:host=override;dbname=custom\nDB_DRIVER=mysql\n");
            Config::load($file);
            self::assertTrue(Config::$dbSettings['dsn'] === 'mysql:host=override;dbname=custom', 'Explicit DSN was not preserved.');

            file_put_contents($file, "DB_DSN=\nDB_DRIVER=unknown\n");
            $this->assertThrows(fn() => Config::load($file), InvalidArgumentException::class);

            file_put_contents($file, "DB_DRIVER=mysql\nDB_HOST=\n");
            $this->assertThrows(fn() => Config::load($file), RuntimeException::class);

            file_put_contents($file, "DB_HOST=database\nDB_DATABASE=\n");
            $this->assertThrows(fn() => Config::load($file), RuntimeException::class);
            $this->assertThrows(fn() => Config::load($file . '-missing'), RuntimeException::class);
        } finally {
            unlink($file);
        }
    }
}
