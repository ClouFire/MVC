<?php

use PHPFramework\Database;

final class HttpTest extends DatabaseTestCase {
    protected bool $importSchema = true;
    private string $address;
    private ?string $sessionDirectory = null;
    private ?string $log = null;
    private $process = null;

    protected function setUp(): void {
        parent::setUp();

        try {
            Database::getInstance()->execute("INSERT INTO products (name, price) VALUES ('counter', 0)");

            $socket = stream_socket_server('tcp://127.0.0.1:0', $errorNumber, $errorMessage);
            self::assertIsResource($socket);
            $this->address = stream_socket_get_name($socket, false);

            fclose($socket);

            $this->sessionDirectory = sys_get_temp_dir() . '/mvc-http-' . bin2hex(random_bytes(8));

            mkdir($this->sessionDirectory);
            putenv('TEST_SESSION_DIRECTORY=' . $this->sessionDirectory);

            $this->log = tempnam(sys_get_temp_dir(), 'mvc-http-log-');
            $this->process = proc_open(
                [PHP_BINARY, '-S', $this->address, __DIR__ . '/support/http_router.php'],
                [['pipe', 'r'], ['file', $this->log, 'a'], ['file', $this->log, 'a']],
                $pipes,
                dirname(__DIR__)
            );

            self::assertIsResource($this->process);

            fclose($pipes[0]);

            for ($attempt = 0; $attempt < 100; $attempt++) {
                $probe = @stream_socket_client('tcp://' . $this->address, $errorNumber, $errorMessage, 0.1);

                if (is_resource($probe)) {
                    fclose($probe);

                    return;
                }

                usleep(50000);
            }

            self::fail('HTTP server did not start: ' . file_get_contents($this->log));
        } catch (Throwable $exception) {
            $this->tearDown();

            throw $exception;
        }
    }

    protected function tearDown(): void {
        try {
            if (is_resource($this->process)) {
                proc_terminate($this->process);
                proc_close($this->process);
            }

            if ($this->sessionDirectory !== null && is_dir($this->sessionDirectory)) {
                foreach (glob($this->sessionDirectory . '/*') as $file) {
                    unlink($file);
                }

                rmdir($this->sessionDirectory);
            }

            if ($this->log !== null && is_file($this->log)) {
                unlink($this->log);
            }

            putenv('TEST_SESSION_DIRECTORY');
        } finally {
            parent::tearDown();
        }
    }

    public function testPublicRoutesAndErrorStatuses(): void {
        $cookie = '';
        $home = $this->request('/', $cookie);

        self::assertSame(200, $home['status']);
        self::assertStringContainsString('Hello from home page', $home['body']);
        self::assertSame(200, $this->request('/register', $cookie)['status']);
        self::assertSame(200, $this->request('/login', $cookie)['status']);
        self::assertSame(404, $this->request('/missing', $cookie)['status']);
        self::assertSame(500, $this->request('/__test/server-error', $cookie)['status']);
        self::assertSame(404, $this->request('/logout', $cookie)['status']);
    }

    public function testGuestsCannotAccessProtectedRoutes(): void {
        $cookie = '';

        foreach (['/dashboard', '/users'] as $path) {
            $response = $this->request($path, $cookie);

            self::assertSame(302, $response['status']);
            self::assertContains('Location: /login', $response['headers']);
        }
    }

    public function testErrorPathsAndRedirectFallback(): void {
        $cookie = '';

        foreach ([
            '/__test/missing-record' => 404,
            '/__test/missing-view' => 500,
            '/__test/missing-layout' => 500,
            '/__test/invalid-page?page=100' => 404,
            '/__test/db-failure' => 500,
        ] as $path => $status) {
            self::assertSame($status, $this->request($path, $cookie)['status']);
        }

        self::assertStringContainsString('DB connection error', file_get_contents($this->sessionDirectory . '/database-error.log'));
        $response = $this->request('/__test/redirect-back', $cookie);

        self::assertSame(302, $response['status']);
        self::assertContains('Location: /', $response['headers']);
    }

    public function testInvalidCsrfTokensAreRejected(): void {
        $cookie = '';

        self::assertSame(419, $this->request('/register', $cookie, ['csrf_token' => 'wrong'])['status']);
        $ajax = $this->request('/register', $cookie, ['csrf_token' => 'wrong'], true);

        self::assertSame('Security error', json_decode($ajax['body'], true, 512, JSON_THROW_ON_ERROR)['data']);
        self::assertSame(0, (int) Database::getInstance()->countAll('users'));
    }

    public function testRegistrationLoginAndLogout(): void {
        [$cookie, $csrf] = $this->registerAndLogin();

        self::assertSame(200, $this->request('/dashboard', $cookie)['status']);
        $users = $this->request('/users', $cookie);

        self::assertSame(200, $users['status']);
        self::assertStringContainsString('HTTP &lt;User&gt;', $users['body']);
        self::assertSame(302, $this->request('/logout', $cookie, ['csrf_token' => $csrf])['status']);
        self::assertSame(302, $this->request('/dashboard', $cookie)['status']);
    }

    public function testDisabledConsolesDoNotExecuteCode(): void {
        [$cookie, $csrf] = $this->registerAndLogin();
        $disabled = $this->request('/test-lab/execute-sql', $cookie, [
            'csrf_token' => $csrf,
            'sql' => 'DELETE FROM users',
        ], true);

        self::assertSame(422, $disabled['status']);
        self::assertSame('error', json_decode($disabled['body'], true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertSame(1, (int) Database::getInstance()->countAll('users'));
        self::assertSame(302, $this->request('/test-lab/execute-php', $cookie, [
            'csrf_token' => $csrf,
            'php_code' => 'return 1;',
        ])['status']);
    }

    public function testParametersAndRedirectHeaders(): void {
        $cookie = '';

        self::assertSame('parameter:42', $this->request('/__test/parameters/42', $cookie)['body']);
        $redirect = $this->request('/__test/redirect', $cookie);

        self::assertSame(302, $redirect['status']);
        self::assertContains('Location: /destination', $redirect['headers']);
    }

    public function testPageCacheSeparatesUrlsAndSessions(): void {
        $cookie = '';

        $this->request('/', $cookie);

        $first = $this->request('/__test/cache?page=1', $cookie);

        self::assertSame($first['body'], $this->request('/__test/cache?page=1', $cookie)['body']);
        self::assertNotSame($first['body'], $this->request('/__test/cache?page=2', $cookie)['body']);
        $anotherCookie = '';

        $this->request('/', $anotherCookie);

        self::assertNotSame($first['body'], $this->request('/__test/cache?page=1', $anotherCookie)['body']);
    }

    public function testNoStoreResponsesAreNotCached(): void {
        $cookie = '';

        $this->request('/', $cookie);

        $first = $this->request('/__test/private', $cookie);
        $second = $this->request('/__test/private', $cookie);

        self::assertContains('Cache-Control: no-store', $second['headers']);
        self::assertNotSame($first['body'], $second['body']);
        $first = $this->request('/__test/upstream-private', $cookie);
        $second = $this->request('/__test/upstream-private', $cookie);

        self::assertContains('Cache-Control: private, no-store', $second['headers']);
        self::assertNotSame($first['body'], $second['body']);
    }

    private function registerAndLogin(): array {
        $cookie = '';
        $login = $this->request('/login', $cookie);

        self::assertSame(1, preg_match('/name="csrf_token" value="([^"]+)"/', $login['body'], $matches));
        $csrf = $matches[1];
        $registration = $this->request('/register', $cookie, [
            'csrf_token' => $csrf,
            'name' => 'HTTP <User>',
            'email' => 'http@example.test',
            'password' => 'httpsecret123',
            'confirmPassword' => 'httpsecret123',
        ]);

        self::assertSame(302, $registration['status']);
        $user = Database::getInstance()->findOne('users', 'http@example.test', 'email');

        self::assertIsArray($user);
        self::assertTrue(password_verify('httpsecret123', $user['password']));
        $oldCookie = $cookie;
        $authenticated = $this->request('/login', $cookie, [
            'csrf_token' => $csrf,
            'email' => 'http@example.test',
            'password' => 'httpsecret123',
        ]);

        self::assertSame(302, $authenticated['status']);
        self::assertNotSame($oldCookie, $cookie);

        return [$cookie, $csrf];
    }

    private function request(string $path, string &$cookie, array $data = [], bool $ajax = false): array {
        $headers = ['Cookie: ' . $cookie];

        if ($ajax) {
            $headers[] = 'X-Requested-With: XMLHttpRequest';
        }

        $options = [
            'method' => $data === [] ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 5,
        ];

        if ($data !== []) {
            $options['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $options['content'] = http_build_query($data);
        }

        $body = file_get_contents('http://' . $this->address . $path, false, stream_context_create(['http' => $options]));
        $responseHeaders = $http_response_header;

        self::assertSame(1, preg_match('/HTTP\/\S+ (\d+)/', $responseHeaders[0], $matches));

        foreach ($responseHeaders as $header) {
            if (preg_match('/^Set-Cookie: (PHPSESSID=[^;]+)/i', $header, $cookieMatches)) {
                $cookie = $cookieMatches[1];
            }
        }

        self::assertStringNotContainsString('Fatal error', $body);
        self::assertStringNotContainsString('Warning:', $body);

        return ['status' => (int) $matches[1], 'headers' => $responseHeaders, 'body' => $body];
    }
}
