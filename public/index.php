<?php

use PHPFramework\Application;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
\PHPFramework\Config::load(__DIR__ . '/../.env');
require_once \PHPFramework\Config::$helpers . '/helpers.php';

$whoops = new Run();

if (\PHPFramework\Config::$debug) {
    $whoops->pushHandler(new PrettyPageHandler());
} else {
    $whoops->pushHandler(new CallbackHandler(function (Throwable $exception) {
        error_log(
            '[' . date('Y-m-d H:i:s') . "] Error: {$exception->getMessage()}" . PHP_EOL
            . "File: {$exception->getFile()}" . PHP_EOL
            . "Line: {$exception->getLine()}" . PHP_EOL
            . '++++++++++++++++++++++++++++' . PHP_EOL,
            3,
            \PHPFramework\Config::$errorLogs
        );

        abort('Server error', 500);
    }));
}

$whoops->register();

$app = new Application();
require_once \PHPFramework\Config::$config . '/routes.php';
require_once \PHPFramework\Config::$config . '/routes.php';

$app->run();
