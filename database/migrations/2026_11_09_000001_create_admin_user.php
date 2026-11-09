<?php

use PHPFramework\Config;
use PHPFramework\Migration;

return new class implements Migration {
    public function up(PDO $connection): void {
        $credentials = Config::$adminCredentials;
        $name = $credentials['name'];
        $email = $credentials['email'];
        $password = $credentials['password'];

        if ($name === '' || strlen($name) > 100) {
            throw new RuntimeException('ADMIN_NAME is required and must not exceed 100 bytes.');
        }

        if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL must be a valid email address of at most 255 bytes.');
        }

        if (strlen($password) < 6 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new RuntimeException('ADMIN_PASSWORD must contain 6 to 72 bytes and no null bytes.');
        }

        $statement = $connection->prepare('SELECT id FROM users WHERE email = ? OR name = ?');

        $statement->execute([$email, $name]);

        if ($statement->fetchColumn() !== false) {
            throw new RuntimeException('An account with ADMIN_EMAIL or ADMIN_NAME already exists. No credentials were changed.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $connection->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');

        $statement->execute([$name, $email, $hash]);
    }
};
