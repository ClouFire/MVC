<?php

/** @var /PHPFramework/Application $app */

use App\Controllers\HomeController;
use App\Controllers\TestLabController;
use App\Controllers\UserController;
use App\Models\User;

const MIDDLEWARE = [
    'auth' => \PHPFramework\Middleware\Auth::class,
    'cache' => \PHPFramework\Middleware\CachePage::class,
];

$app->router->get(str_replace('/', '', \PHPFramework\Config::$path), [HomeController::class,'getHomePage']);
$app->router->get('register', [UserController::class,'register']);
$app->router->post('register', [UserController::class,'store']);
$app->router->get('login', [UserController::class,'login']);
$app->router->post('login', [UserController::class,'authenticate']);
$app->router->get('dashboard', [HomeController::class, 'dashboard'])->middleware(['auth']);
$app->router->post('logout', [UserController::class, 'logout']);
$app->router->get('users', [UserController::class, 'index'])->middleware(['auth']);
$app->router->post('dashboard/create-user', [HomeController::class, 'createUser'])->middleware(['auth']);
// Add ->middleware(['auth']) here to restrict cache clearing to authenticated users.
$app->router->post('test-lab/cache/clear', [TestLabController::class, 'clearCache']);
$app->router->post('test-lab/execute-php', [TestLabController::class, 'executePhp'])->middleware(['auth']);

/* $app->router->get('/post/(?P<slug>[a-z0-9-]+)/?', function() {
    return '<p>Some post</p>';
});
*/
