<?php

abstract class DatabaseTestCase extends FrameworkTestCase {
    protected bool $importSchema = false;
    private ?PDO $administrationConnection = null;
    private ?string $databaseName = null;
    private string $originalDsn;

    protected function setUp(): void {
        parent::setUp();

        $dsn = getenv('TEST_DB_DSN');

        if (!$dsn) {
            self::fail('Integration tests require TEST_DB_DSN pointing to a disposable MySQL server.');
        }

        $this->originalDsn = $dsn;
        $this->administrationConnection = new PDO($dsn, getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $name = 'mvc_test_' . bin2hex(random_bytes(8));

        $this->administrationConnection->exec('CREATE DATABASE ' . $name);

        $this->databaseName = $name;
        $dsn = preg_replace('/;?dbname=[^;]*/i', '', $dsn);

        try {
            putenv('TEST_DB_DSN=' . $dsn . ';dbname=' . $name);
            configureTestApplication();
            configureTestDatabase();

            if ($this->importSchema) {
                require __DIR__ . '/schema.php';
            }
        } catch (Throwable $exception) {
            $this->tearDown();

            throw $exception;
        }
    }

    protected function tearDown(): void {
        try {
            if ($this->databaseName !== null) {
                $this->administrationConnection->exec('DROP DATABASE ' . $this->databaseName);
                $this->databaseName = null;
            }
        } finally {
            if (isset($this->originalDsn)) {
                putenv('TEST_DB_DSN=' . $this->originalDsn);
            }

            parent::tearDown();
        }
    }
}
