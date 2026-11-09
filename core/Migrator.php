<?php

namespace PHPFramework;

final class Migrator {
    private \PDO $connection;
    private string $directory;

    public function __construct(\PDO $connection, string $directory) {
        $this->connection = $connection;
        $this->directory = $directory;
    }

    public function migrate(): array {
        if (!is_dir($this->directory)) {
            throw new \RuntimeException('Migration directory does not exist.');
        }

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                migration VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $files = glob($this->directory . '/*.php');

        if ($files === false) {
            throw new \RuntimeException('Unable to read migration directory.');
        }

        sort($files, SORT_STRING);

        $applied = $this->connection->query('SELECT migration FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);
        $completed = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;

            if (!$migration instanceof Migration) {
                throw new \RuntimeException("Invalid migration: {$name}");
            }

            $this->connection->beginTransaction();

            try {
                $migration->up($this->connection);
                $statement = $this->connection->prepare('INSERT INTO migrations (migration) VALUES (?)');

                $statement->execute([$name]);

                if ($this->connection->inTransaction()) {
                    $this->connection->commit();
                }
            } catch (\Throwable $exception) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }

                throw new \RuntimeException("Migration failed: {$name}", 0, $exception);
            }

            $completed[] = $name;
        }

        return $completed;
    }
}
