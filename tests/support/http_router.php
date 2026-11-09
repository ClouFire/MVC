<?php

require __DIR__ . '/subprocess_coverage.php';

use PHPFramework\Application;
use PHPFramework\Middleware\CachePage;

require __DIR__ . '/runtime.php';
require __DIR__ . '/bootstrap.php';

configureTestApplication();
configureTestDatabase();

session_save_path(getenv('TEST_SESSION_DIRECTORY'));
session_cache_limiter('');

if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/__test/upstream-private') {
    header('Cache-Control: private, no-store');
}

if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/__test/db-failure') {
    \PHPFramework\Config::$dbSettings['dsn'] = 'mysql:host=127.0.0.1;port=1;dbname=unavailable';
    \PHPFramework\Config::$errorLogs = getenv('TEST_SESSION_DIRECTORY') . '/database-error.log';
}

$app = new Application();

require dirname(__DIR__, 2) . '/config/routes.php';

$app->router->get('__test/redirect', function () {
    response()->redirect('/destination');
});
$app->router->get('__test/cache', function () {
    db()->execute("UPDATE products SET price = price + 1 WHERE name = 'counter'");

    return (string) db()->findOne('products', 'counter', 'name')['price'];
})->middleware(['cache']);
$app->router->get('__test/private', function () {
    header('Cache-Control: no-store');

    db()->execute("UPDATE products SET price = price + 1 WHERE name = 'counter'");

    return (string) db()->findOne('products', 'counter', 'name')['price'];
})->middleware(['cache']);
$app->router->get('__test/upstream-private', function () {
    db()->execute("UPDATE products SET price = price + 1 WHERE name = 'counter'");

    return (string) db()->findOne('products', 'counter', 'name')['price'];
})->middleware(['cache']);
$app->router->get('__test/server-error', function () {
    abort('Test server error', 500);
});
$app->router->get('__test/parameters/(?P<id>[0-9]+)', fn(string $id) => 'parameter:' . $id);
$app->router->get('__test/redirect-back', function () {
    response()->redirect();
});
$app->router->get('__test/missing-record', function () {
    db()->findOrFail('users', 0);
});
$app->router->get('__test/missing-view', fn() => view('missing-view'));
$app->router->get('__test/missing-layout', fn() => view('home', [], 'missing-layout'));
$app->router->get('__test/invalid-page', function () {
    return (new \PHPFramework\Pagination(2))->getPages();
});

$app->run();
