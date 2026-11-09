<?php

class FailedSaveDatabase extends \PHPFramework\Database {
    public function __construct(PDO $connection) {
        $property = new ReflectionProperty(\PHPFramework\Database::class, 'connection');

        $property->setAccessible(true);
        $property->setValue($this, $connection);
    }

    public function execute(string $statement, array $params = []): \PHPFramework\Database {
        if (str_starts_with($statement, 'INSERT INTO users')) {
            return $this;
        }

        return parent::execute($statement, $params);
    }

    public function getInsertId(): false|string {
        return false;
    }
}
