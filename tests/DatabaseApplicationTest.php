<?php

use App\Controllers\HomeController;
use App\Controllers\TestLabController;
use App\Controllers\UserController;
use App\Models\User;
use PHPFramework\Application;
use PHPFramework\Config;
use PHPFramework\Database;
use PHPFramework\Middleware\Auth;
use PHPFramework\Request;
use PHPFramework\Response;
use PHPFramework\View;

class RedirectSignal extends RuntimeException {
}

class TestResponse extends Response {
    public function redirect($url = ''): void {
        throw new RedirectSignal($url);
    }
}

final class DatabaseApplicationTest extends DatabaseTestCase {
    protected bool $importSchema = true;

    protected function setUp(): void {
        parent::setUp();

        configureTestApplication();
        configureTestDatabase();

        session_save_path(sys_get_temp_dir());

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('a', 600);
        $app = new Application();

        $app->response = new TestResponse();
    }

    public function testDatabaseOperations(): void {
        $app = app();
        $db = db();

        self::assertTrue(Database::getInstance() === $app->db, 'Database connection was not reused.');
        $this->assertThrows(fn() => clone $db, BadMethodCallException::class);
        self::assertSame(1, $db->execute('SELECT 1 AS value')->getOne()['value']);
        $db->execute('SELECT 1');
        self::assertSame(1, $db->rowCount());
        self::assertTrue(Database::prepare('SELECT 1')->execute(), 'Database prepare failed.');

        $app = app();
        $db = db();
        $id = $db->insert('products', ['name', 'price'], ['Test product', 12]);

        self::assertTrue($id !== false && $db->findOne('products', $id)['name'] === 'Test product', 'Database insert failed.');
        self::assertTrue($db->findOrFail('products', $id)['id'] == $id, 'Existing record was not found.');
        self::assertTrue($db->countAll('products') == 1 && count($db->findAll('products')) === 1, 'Database counts failed.');
        self::assertTrue(count($db->findRange('products', 1, 0)) === 1, 'Database range failed.');
        self::assertTrue(isset($db->execute('SELECT * FROM products')->getAssoc()[$id]), 'Associative database result failed.');
        $db->beginTransaction();
        $db->execute('UPDATE products SET price = ? WHERE id = ?', [99, $id]);
        $db->rollBack();
        self::assertTrue((float) $db->findOne('products', $id)['price'] === 12.0, 'Rollback failed.');
        $db->beginTransaction();
        $db->execute('UPDATE products SET price = ? WHERE id = ?', [15, $id]);
        $db->commit();
        self::assertTrue((float) $db->findOne('products', $id)['price'] === 15.0, 'Commit failed.');
        $this->assertThrows(fn() => $db->delete('products', '0 OR 1=1'), PDOException::class);
        self::assertTrue($db->countAll('products') == 1, 'Delete did not bind its value.');
        $db->delete('products', $id);
        self::assertTrue($db->countAll('products') == 0, 'Delete failed.');
        $this->assertThrows(fn() => $db->insert('products', ['name'], []), InvalidArgumentException::class);
        $this->assertThrows(fn() => $db->__wakeup(), BadMethodCallException::class);
    }

    public function testControllerFlows(): void {
        $app = app();
        $db = db();

        $controller = new UserController();

        $this->input(['name' => 'Alice', 'email' => 'alice@example.test', 'password' => 'secret123', 'confirmPassword' => 'secret123']);
        $this->redirects(fn() => $controller->store(), '/login');
        $user = $db->findOne('users', 'alice@example.test', 'email');
        self::assertTrue(password_verify('secret123', $user['password']), 'Registration did not hash the password.');
        self::assertTrue(!isset($user['confirmPassword']), 'Non-fillable model field was saved.');

        $this->input(['name' => 'Alice', 'email' => 'alice@example.test', 'password' => 'secret123', 'confirmPassword' => 'secret123']);
        $this->redirects(fn() => $controller->store(), '/register');
        self::assertTrue($db->countAll('users') == 1 && session()->get('form_errors') !== null, 'Duplicate registration was accepted.');

        $this->input(['email' => 'not-an-email', 'password' => 'secret123']);
        $this->redirects(fn() => $controller->authenticate(), '/login');
        self::assertTrue(!isAuth() && isset(session()->get('form_errors')['email']), 'Malformed login was accepted.');

        $this->input(['email' => 'alice@example.test', 'password' => 'wrong']);
        $this->redirects(fn() => $controller->authenticate(), '/login');
        self::assertTrue(!isAuth(), 'Incorrect password authenticated.');

        $this->input(['email' => 'missing@example.test', 'password' => 'wrong']);
        $this->redirects(fn() => $controller->authenticate(), '/login');
        self::assertTrue(!isAuth(), 'Unknown user authenticated.');

        $this->input(['email' => 'alice@example.test', 'password' => 'secret123']);
        $oldSession = session_id();

        $this->redirects(fn() => $controller->authenticate(), '/dashboard');
        self::assertTrue(isAuth() && session_id() !== $oldSession, 'Login did not authenticate or rotate the session.');
        self::assertTrue(!isset(auth()['password']), 'Password hash leaked into authenticated session.');
        $this->redirects(fn() => $controller->login(), '/dashboard');
        (new Auth())->handle();

        $home = new HomeController();

        self::assertTrue(str_contains($home->getHomePage(), 'Hello from home page'), 'Home page did not render.');
        self::assertTrue(str_contains($home->dashboard(), 'Test action log'), 'Dashboard did not render.');
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $app->request = new Request('/users');

        self::assertTrue(str_contains($controller->index(), 'Alice'), 'User list did not render database data.');

        $this->input(['name' => 'Lab user', 'email' => 'lab@example.test', 'password' => 'labsecret']);
        $this->redirects(fn() => $home->createUser(), '/dashboard');
        self::assertTrue(password_verify('labsecret', $db->findOne('users', 'lab@example.test', 'email')['password']), 'Lab user was not saved correctly.');
        self::assertTrue($db->getTestActionLog(1)[0]['action'] === 'create_test_user', 'Lab user action was not logged.');

        $this->input(['name' => 'Lab user', 'email' => 'lab@example.test', 'password' => 'labsecret']);
        $this->redirects(fn() => $home->createUser(), '/dashboard');
        self::assertTrue($db->getTestActionLog(1)[0]['action'] === 'create_test_user_failed', 'Failed lab user action was not logged.');

        $lab = new TestLabController();
        Config::$testLabPhpExecutionEnabled = false;

        $this->input(['php_code' => 'throw new Exception("must not run");']);
        $this->redirects(fn() => $lab->executePhp(), '/');
        self::assertTrue(str_contains(session()->getFlash('php_execution')['error'], 'disabled'), 'Disabled PHP console ran.');
        Config::$testLabPhpExecutionEnabled = true;

        $this->input(['php_code' => '']);
        $this->redirects(fn() => $lab->executePhp(), '/');
        self::assertTrue(session()->getFlash('php_execution')['status'] === 'error', 'Empty PHP code was accepted.');

        $this->input(['php_code' => 'echo "example"; return 42;']);
        $this->redirects(fn() => $lab->executePhp(), '/');
        $result = session()->getFlash('php_execution');
        self::assertTrue($result['output'] === 'example' && $result['return'] === '42', 'PHP console result is incorrect.');

        $this->input(['php_code' => 'throw new RuntimeException("test failure");']);
        $this->redirects(fn() => $lab->executePhp(), '/');
        self::assertTrue(session()->getFlash('php_execution')['status'] === 'error', 'PHP exception was not reported.');

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        Config::$testLabSqlConsoleEnabled = false;

        $this->input(['sql' => 'DELETE FROM users']);
        ob_start();
        $lab->executeSql();
        $result = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['status'] === 'error' && $db->countAll('users') == 2, 'Disabled SQL console executed a query.');
        Config::$testLabSqlConsoleEnabled = true;

        foreach ([
            ['', 'error'],
            ['SELECT name FROM users ORDER BY id', 'success'],
            ["UPDATE users SET name = 'Alice Updated' WHERE email = 'alice@example.test'", 'success'],
            ['INVALID SQL STATEMENT', 'error'],
        ] as [$sql, $status]) {
            $this->input(['sql' => $sql]);
            ob_start();
            $lab->executeSql();
            $result = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
            self::assertTrue($result['status'] === $status, 'SQL console returned incorrect status.');
        }

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $this->input(['sql' => 'SELECT 1']);
        $this->redirects(fn() => $lab->executeSql(), '/');
        self::assertTrue(session()->getFlash('sql_console')['status'] === 'success', 'SQL form fallback did not preserve the result.');
        cache()->set('example', 'value');
        $this->redirects(fn() => $lab->clearCache(), '/');
        self::assertTrue(cache()->get('example') === null, 'Cache clear action failed.');
        $entry = $db->getTestActionLog(1)[0];
        self::assertTrue($entry['action'] === 'clear_cache' && strlen($entry['user_agent']) === 512, 'Audit log metadata is incorrect.');
        self::assertTrue(count($db->getTestActionLog(0)) === 1, 'Audit log limit was not bounded.');

        $this->redirects(fn() => $controller->logout(), '/login');
        self::assertTrue(!isAuth(), 'Logout left the user authenticated.');
        $this->redirects(fn() => (new Auth())->handle(), '/login');
    }

    public function testMigrationOrdering(): void {
        $app = app();
        $db = db();

        $directory = sys_get_temp_dir() . '/mvc-order-' . bin2hex(random_bytes(8));

        $this->assertThrows(fn() => (new \PHPFramework\Migrator(Database::connection(), $directory))->migrate(), RuntimeException::class);
        mkdir($directory);

        try {
            file_put_contents($directory . '/invalid.php', '<?php return true;');

            $this->assertThrows(fn() => (new \PHPFramework\Migrator(Database::connection(), $directory))->migrate(), RuntimeException::class);
            unlink($directory . '/invalid.php');

            foreach (['002_second', '001_first'] as $name) {
                $source = <<<'SOURCE'
        <?php
        return new class implements \PHPFramework\Migration {
            public function up(\PDO $connection): void {
                $connection->exec("INSERT INTO products (name) VALUES ('MIGRATION_NAME')");
            }
        };
        SOURCE;

                file_put_contents($directory . '/' . $name . '.php', str_replace('MIGRATION_NAME', $name, $source));
            }

            $migrator = new \PHPFramework\Migrator(Database::connection(), $directory);

            self::assertTrue($migrator->migrate() === ['001_first', '002_second'], 'Migration files were not sorted.');
            self::assertTrue($migrator->migrate() === [], 'Ordered migrations were applied twice.');
            self::assertTrue($db->execute('SELECT name FROM products ORDER BY id')->getColumn() === '001_first', 'Migrations executed in the wrong order.');
        } finally {
            foreach (glob($directory . '/*.php') as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    private function redirects(callable $operation, string $path): void {
        $exception = $this->assertThrows($operation, RedirectSignal::class);

        self::assertTrue($exception->getMessage() === $path, 'Incorrect redirect: ' . $exception->getMessage());
    }

    public function testControllersReportFailedPersistenceWithoutClaimingSuccess(): void {
        require __DIR__ . '/support/FailedSaveDatabase.php';

        app()->db = new FailedSaveDatabase(Database::connection());
        $this->input(['name' => 'Unsaved', 'email' => 'unsaved@example.test', 'password' => 'secret123', 'confirmPassword' => 'secret123']);

        $this->redirects(fn() => (new UserController())->store(), '/register');
        self::assertSame('Registration error', session()->getFlash('error'));
        $this->redirects(fn() => (new HomeController())->createUser(), '/dashboard');
        self::assertSame('User could not be created.', session()->getFlash('error'));
        self::assertSame(0, (int) db()->countAll('users'));
        self::assertSame('create_test_user_failed', db()->getTestActionLog(1)[0]['action']);
    }

    public function testTimestampedModelPersistsCreationAndUpdateTimes(): void {
        db()->execute('CREATE TABLE timestamped (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), created_at DATETIME, updated_at DATETIME)');
        $model = new class extends \PHPFramework\Model {
            protected string $table = 'timestamped';
            protected bool $timestamps = true;
            protected array $fillable = ['name'];
        };
        $model->attrs = ['name' => 'Timestamped'];

        $id = $model->save();
        $record = db()->findOne('timestamped', $id);

        self::assertSame('Timestamped', $record['name']);
        self::assertNotEmpty($record['created_at']);
        self::assertSame($record['created_at'], $record['updated_at']);
    }

    private function input(array $data): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        $_SESSION['flash'] = [];
    }
}
