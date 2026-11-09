<?php

use PHPFramework\Database;

final class CliTest extends DatabaseTestCase {
    protected bool $importSchema = true;

    public function testMigrationCommand(): void {
        configureTestApplication();
        configureTestDatabase();

        $directory = sys_get_temp_dir() . '/mvc-cli-' . bin2hex(random_bytes(8));

        mkdir($directory);
        mkdir($directory . '/database');
        copy(dirname(__DIR__) . '/migrate.php', $directory . '/migrate.php');
        symlink(dirname(__DIR__) . '/vendor', $directory . '/vendor');
        symlink(dirname(__DIR__) . '/database/migrations', $directory . '/database/migrations');

        try {
            $missing = $this->executeMigration($directory);

            self::assertTrue($missing['status'] === 1 && str_contains($missing['error'], 'Environment file not found'), 'Missing environment did not fail cleanly.');

            $environment = 'DB_DSN=' . getenv('TEST_DB_DSN') . PHP_EOL
                . 'DB_USERNAME=' . (getenv('TEST_DB_USERNAME') ?: 'root') . PHP_EOL
                . 'DB_PASSWORD=' . (getenv('TEST_DB_PASSWORD') ?: '') . PHP_EOL
                . 'ADMIN_NAME=CLI Administrator' . PHP_EOL
                . 'ADMIN_EMAIL=cli@example.test' . PHP_EOL;

            file_put_contents($directory . '/.env', $environment . 'ADMIN_PASSWORD=short' . PHP_EOL);

            $invalid = $this->executeMigration($directory);

            self::assertTrue($invalid['status'] === 1 && str_contains($invalid['error'], 'ADMIN_PASSWORD'), 'Invalid credentials did not fail the CLI.');
            self::assertTrue(Database::getInstance()->countAll('users') == 0, 'Failed CLI migration created a user.');

            file_put_contents($directory . '/.env', $environment . 'ADMIN_PASSWORD=cli-secret-password' . PHP_EOL);

            $success = $this->executeMigration($directory);

            self::assertTrue($success['status'] === 0 && str_contains($success['output'], 'Applied:'), 'CLI migration failed.');
            self::assertTrue(!str_contains($success['output'] . $success['error'], 'cli-secret-password'), 'CLI exposed the password.');
            self::assertTrue(password_verify('cli-secret-password', Database::getInstance()->findOne('users', 'cli@example.test', 'email')['password']), 'CLI password hash is invalid.');
            self::assertTrue($this->executeMigration($directory)['output'] === 'No pending migrations.' . PHP_EOL, 'CLI repeated an applied migration.');
        } finally {
            foreach (['.env', 'migrate.php', 'vendor', 'database/migrations'] as $path) {
                if (file_exists($directory . '/' . $path)) {
                    unlink($directory . '/' . $path);
                }
            }

            rmdir($directory . '/database');
            rmdir($directory);
        }
    }

    private function executeMigration(string $directory): array {
        $process = proc_open(
            [PHP_BINARY, '-d', 'auto_prepend_file=' . __DIR__ . '/support/runtime.php', $directory . '/migrate.php'],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes
        );

        self::assertTrue(is_resource($process), 'Unable to start migration CLI.');

        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['status' => proc_close($process), 'output' => $output, 'error' => $error];
    }
}
