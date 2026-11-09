<?php

use PHPFramework\Config;
use PHPFramework\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

$dsn = getenv('TEST_DB_DSN');

if (!$dsn) {
    throw new RuntimeException('Set TEST_DB_DSN to an empty, disposable MySQL database.');
}

$connection = new PDO($dsn, getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

if ($connection->query('SHOW TABLES')->fetchAll() !== []) {
    throw new RuntimeException('Tests require an empty database.');
}

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectFailure(callable $operation): void {
    try {
        $operation();
    } catch (RuntimeException $exception) {
        return;
    }

    throw new RuntimeException('Expected migration failure.');
}

$connection->exec(
    'CREATE TABLE users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$migrator = new Migrator($connection, dirname(__DIR__) . '/database/migrations');
Config::$adminCredentials = ['name' => '', 'email' => '', 'password' => ''];

expectFailure(fn() => $migrator->migrate());
check((int) $connection->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === 0, 'Failed migration was recorded.');

foreach ([
    ['name' => 'Admin', 'email' => 'invalid', 'password' => 'test-password-123'],
    ['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'short'],
] as $credentials) {
    Config::$adminCredentials = $credentials;

    expectFailure(fn() => $migrator->migrate());
}

check((int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'Invalid credentials created an account.');

Config::$adminCredentials = ['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password-123'];
$statement = $connection->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');

$statement->execute(['Existing', 'admin@example.test', 'unchanged']);

expectFailure(fn() => $migrator->migrate());
check($connection->query('SELECT password FROM users')->fetchColumn() === 'unchanged', 'Existing password changed.');

$connection->exec('DELETE FROM users');

check(count($migrator->migrate()) === 1, 'Migration was not applied.');

$user = $connection->query('SELECT * FROM users')->fetch();

check($user['name'] === 'Admin' && $user['email'] === 'admin@example.test', 'Incorrect account.');
check(password_verify('test-password-123', $user['password']), 'Password hash is invalid.');

Config::$adminCredentials = ['name' => '', 'email' => '', 'password' => ''];

check($migrator->migrate() === [], 'Completed migration ran again.');
check((int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Duplicate account created.');

$directory = sys_get_temp_dir() . '/mvc-migrations-' . bin2hex(random_bytes(8));

mkdir($directory);
file_put_contents($directory . '/9999_failure.php', <<<'PHP'
<?php
return new class implements \PHPFramework\Migration {
    public function up(\PDO $connection): void {
        $connection->exec("INSERT INTO users (name, email, password) VALUES ('Rollback', 'rollback@example.test', 'unused')");

        throw new \RuntimeException('Intentional failure');
    }
};
PHP);

try {
    expectFailure(fn() => (new Migrator($connection, $directory))->migrate());
    check((int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Failed migration did not roll back.');
    check((int) $connection->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === 1, 'Failed migration was recorded.');
} finally {
    unlink($directory . '/9999_failure.php');
    rmdir($directory);
}

fwrite(STDOUT, 'Migration integration tests passed.' . PHP_EOL);
