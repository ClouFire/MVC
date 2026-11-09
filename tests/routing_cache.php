<?php

use PHPFramework\Application;
use PHPFramework\Cache;
use PHPFramework\Config;
use PHPFramework\Middleware\CachePage;
use PHPFramework\Request;
use PHPFramework\Response;
use PHPFramework\Router;
use PHPFramework\Session;
use PHPFramework\View;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/helpers/helpers.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class MemoryCache extends Cache {
    public array $values = [];

    public function get($key, $default = null) {
        return $this->values[$key] ?? $default;
    }

    public function set($key, $data, $seconds = 3600): void {
        $this->values[$key] = $data;
    }
}

class TestGuard {
    public static int $calls = 0;
    public static bool $allowed = true;

    public function handle(): void {
        self::$calls++;

        if (!self::$allowed) {
            throw new RuntimeException('Access denied');
        }
    }
}

class ParameterController {
    public function show(string $id, string $slug): string {
        return "{$id}:{$slug}";
    }
}

const MIDDLEWARE = ['cache' => CachePage::class, 'guard' => TestGuard::class];
Config::$path = '/';
Config::$views = dirname(__DIR__) . '/app/Views';
$app = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
Application::$app = $app;
session_save_path(sys_get_temp_dir());
$app->session = new Session();
$app->cache = new MemoryCache();
$app->response = new Response();
$app->view = new View('default');

function routerFor(string $uri, string $method = 'GET'): Router {
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REQUEST_METHOD'] = $method;
    app()->request = new Request($uri);

    return new Router(app()->request, app()->response);
}

$router = routerFor('/users/42/posts/hello');

$router->get('users/(?P<id>[0-9]+)/posts/(?P<slug>[a-z]+)', [ParameterController::class, 'show']);
check($router->dispatch() === '42:hello', 'Controller parameters were not passed in URL order.');

$app->request->uri = 'plain';

$router->get('plain', fn() => 'plain');
check($router->dispatch() === 'plain', 'Route parameters leaked into a later dispatch.');

$router = routerFor('/articles/example');

$router->get('articles/(?P<slug>[a-z]+)', fn(string $slug) => $slug);
check($router->dispatch() === 'example', 'Closure parameter was not passed.');

$router = routerFor('/users?page=1');
$calls = 0;

$router->get('users', function () use (&$calls) {
    $calls++;

    return 'controller-data-' . $calls;
})->middleware(['cache', 'guard']);

check($router->dispatch() === 'controller-data-1', 'Cache miss did not run the controller.');
check($router->dispatch() === 'controller-data-1' && $calls === 1, 'Cache hit ran the controller.');
check(TestGuard::$calls === 2, 'Cache hit bypassed guard middleware.');
TestGuard::$allowed = false;
$blocked = false;

try {
    $router->dispatch();
} catch (RuntimeException $exception) {
    $blocked = $exception->getMessage() === 'Access denied';
}

check($blocked, 'Cached content bypassed authorization.');
TestGuard::$allowed = true;
$page = new CachePage();
$key = $page->getKey();
$_SERVER['REQUEST_URI'] = '/users?page=2';

check($key !== $page->getKey(), 'Query string was omitted from key.');
$_SERVER['REQUEST_URI'] = '/admin/users?page=1';

check($key !== $page->getKey(), 'Full path was omitted from key.');
$_SERVER['REQUEST_URI'] = '/users?page=1';
$_SESSION['user'] = ['id' => 2];

check($key !== $page->getKey(), 'User context was omitted from key.');
$_SESSION = ['csrf_token' => 'token-a'];
$key = $page->getKey();
$_SESSION['csrf_token'] = 'token-b';

check($key !== $page->getKey(), 'CSRF context was omitted from key.');
$_SESSION = [];
$_SESSION['flash'] = ['success' => 'Saved'];

check($router->dispatch() === 'controller-data-2', 'Flash response was served from cache.');
$_SESSION = [];

check($router->dispatch() === 'controller-data-1', 'Flash response replaced cached content.');

$router = routerFor('/session-change');
$calls = 0;

$router->get('session-change', function () use (&$calls) {
    $calls++;
    $_SESSION['changed'] = true;

    return 'changed';
})->middleware(['cache']);

$router->dispatch();
$_SESSION = [];
$router->dispatch();
check($calls === 2, 'Session-changing response was cached.');

$router = routerFor('/error');
$calls = 0;

$router->get('error', function () use (&$calls) {
    $calls++;

    http_response_code(422);

    return 'invalid';
})->middleware(['cache']);

$router->dispatch();
http_response_code(200);
$router->dispatch();
check($calls === 2, 'Error response was cached.');
http_response_code(200);

$router = routerFor('/empty');
$calls = 0;

$router->get('empty', function () use (&$calls) {
    $calls++;

    return '';
})->middleware(['cache']);

$router->dispatch();
$router->dispatch();
check($calls === 1, 'Empty response was treated as a cache miss.');

$router = routerFor('/write', 'POST');
$calls = 0;

$router->post('write', function () use (&$calls) {
    $calls++;

    return 'written';
})->disableCsrfToken()->middleware(['cache']);

$router->dispatch();
$router->dispatch();
check($calls === 2, 'POST response was cached.');

$router = routerFor('/echo');
$calls = 0;

$router->get('echo', function () use (&$calls) {
    $calls++;

    echo 'output';

    return 'return';
})->middleware(['cache']);

ob_start();

$result = $router->dispatch();
$router->dispatch();
$output = ob_get_clean();

check($output === 'outputoutput' && $result === 'return' && $calls === 2, 'Echoed output was lost or cached.');

$router = routerFor('/throw');

$router->get('throw', function () {
    echo 'partial';

    throw new RuntimeException('Expected failure');
})->middleware(['cache']);

$level = ob_get_level();

try {
    $router->dispatch();
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Expected failure', 'Unexpected exception.');
}

check(ob_get_level() === $level, 'Failed response leaked an output buffer.');

$app->cache->values['home'] = 'stale cached template';

check(view('home', ['title' => 'Fresh'], false) !== 'stale cached template', 'View rendering still reads page cache.');

fwrite(STDOUT, 'Routing and page-cache tests passed.' . PHP_EOL);
