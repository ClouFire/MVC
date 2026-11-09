<?php

use PHPFramework\Database;
use PHPFramework\Migrator;

final class MigrationFilesystemTest extends DatabaseTestCase {
    public function testMigrationEnumerationFailureIsReported(): void {
        require __DIR__ . '/support/failing_filesystem.php';

        $exception = $this->assertThrows(
            fn() => (new Migrator(Database::connection(), __DIR__))->migrate(),
            RuntimeException::class
        );

        self::assertSame('Unable to read migration directory.', $exception->getMessage());
        self::assertSame(0, (int) Database::connection()->query('SELECT COUNT(*) FROM migrations')->fetchColumn());
    }
}
