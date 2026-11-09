<?php

use App\Models\User;
use PHPFramework\Application;
use PHPFramework\Pagination;
use PHPFramework\Request;
use PHPFramework\Response;
use PHPFramework\Router;
use PHPFramework\Session;
use PHPFramework\View;
use PHPFramework\Config;

final class CoreTest extends FrameworkTestCase {
    protected function setUp(): void {
        parent::setUp();

        configureTestApplication();

        $app = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
        Application::$app = $app;

        session_save_path(sys_get_temp_dir());

        $app->session = new Session();
        $app->request = new Request('/portal/users?page=2');
        $app->response = new Response();
        $app->view = new View('default');
        Config::$path = '/portal/';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => '  Alice  ', 'items' => ['one'], 'email' => 'alice@example.test'];
        $_GET = ['page' => 2];
    }

    public function testRequestInput(): void {
        $app = app();

        self::assertTrue(request()->getPath() === 'users', 'Base path or query removal failed.');
        self::assertTrue(request()->isPost() && !request()->isGet(), 'Method detection failed.');
        self::assertTrue(request()->getData()['name'] === 'Alice', 'Input was not trimmed.');
        self::assertTrue(request()->getData()['items'] === ['one'], 'Array input was changed.');
        self::assertTrue(request()->post('missing') === false && request()->get('missing', 9) === 9, 'Missing input defaults failed.');
        self::assertTrue(basePath('login') === '/portal/login', 'Base URL construction failed.');
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        self::assertTrue(request()->isAjax(), 'AJAX detection failed.');

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        self::assertTrue(!request()->isAjax(), 'Non-AJAX request was misidentified.');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        self::assertTrue(request()->getData() === $_GET, 'GET input was not used.');
    }

    public function testCsrfTokens(): void {
        $app = app();

        $app->writeCsrfToken();
        $token = session()->get('csrf_token');

        self::assertTrue(strlen($token) === 64, 'CSRF token is invalid.');
        $app->writeCsrfToken();
        self::assertTrue(session()->get('csrf_token') === $token, 'CSRF token unexpectedly changed.');
        self::assertTrue(str_contains(getCsrfField(), $token), 'CSRF field is missing its token.');

        $router = new Router(request(), response());
        $_POST['csrf_token'] = $token;

        self::assertTrue($router->checkCsrf(), 'Valid CSRF token was rejected.');
        $_POST['csrf_token'] = 'wrong';

        self::assertTrue(!$router->checkCsrf(), 'Invalid CSRF token was accepted.');
    }

    public function testSessionLifecycle(): void {
        $app = app();

        $app->writeCsrfToken();
        $token = session()->get('csrf_token');

        session()->set('value', false);
        self::assertTrue(session()->has('value') && session()->get('value') === false, 'False session value was lost.');
        session()->remove('value');
        self::assertTrue(session()->get('value', 'fallback') === 'fallback', 'Session removal failed.');
        session()->setFlash('success', 'Saved');
        self::assertTrue(session()->getFlash('success') === 'Saved', 'Flash value was lost.');
        self::assertTrue(session()->getFlash('success', 'gone') === 'gone', 'Flash value was not consumed.');
        $oldId = session_id();
        session()->regenerate();
        self::assertTrue(session_id() !== $oldId && session()->get('csrf_token') === $token, 'Session regeneration failed.');
    }

    public function testAuthenticationSession(): void {
        $app = app();

        self::assertTrue(!isAuth() && auth() === null, 'Guest was authenticated.');
        session()->set('user', 'invalid');
        self::assertTrue(!isAuth(), 'Invalid user session was authenticated.');
        session()->set('user', ['id' => 7, 'name' => 'Alice']);
        self::assertTrue(isAuth() && auth()['id'] === 7, 'Authenticated user was not resolved.');
        session()->remove('user');
    }

    public function testFormHelpers(): void {
        $app = app();

        self::assertTrue(h('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML escaping failed.');
        session()->set('form_data', ['name' => '<Alice>']);
        session()->set('form_errors', ['name' => ['<invalid>']]);
        self::assertTrue(old('name') === '&lt;Alice&gt;', 'Old input was not escaped.');
        self::assertTrue(str_contains(get_errors('name'), '&lt;invalid&gt;'), 'Validation errors were not escaped.');
        self::assertTrue(getValidationClass('name') === 'is-invalid', 'Invalid field class failed.');
        self::assertTrue(getValidationClass('email') === 'is-valid', 'Valid field class failed.');
        session()->remove('form_errors');
        self::assertTrue(getValidationClass('name') === '', 'Pristine field class failed.');
    }

    public function testApplicationRegistry(): void {
        $app = app();

        $app->set('service', 'registered');
        self::assertTrue($app->get('service') === 'registered' && $app->get('missing', 'default') === 'default', 'Application registry failed.');
    }

    public function testModelValidation(): void {
        $app = app();

        $user = new User();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => ' Alice ', 'email' => 'alice@example.test', 'password' => 'secret123', 'confirmPassword' => 'secret123', 'admin' => true];

        $user->loadData();
        self::assertTrue($user->attrs['name'] === 'Alice' && !isset($user->attrs['admin']), 'Model input allowlist failed.');
        $user->addValidationRule('uniqueName', fn() => true, 'Duplicate name');
        $user->addValidationRule('uniqueEmail', fn() => true, 'Duplicate email');
        self::assertTrue($user->validate(), 'Valid user was rejected.');
        $user->attrs['email'] = 'invalid';
        $user->attrs['confirmPassword'] = 'different';
        self::assertTrue(!$user->validate() && isset($user->getErrors()['email'], $user->getErrors()['password']), 'Invalid user was accepted.');
        $user->attrs['email'] = 'alice@example.test';
        $user->attrs['confirmPassword'] = 'secret123';
        self::assertTrue($user->validate() && $user->getErrors() === [], 'Validation errors survived successful revalidation.');
    }

    public function testPagination(): void {
        $app = app();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        Config::$path = '/';
        $app->request = new Request('/admin/users?search=Alice&page=2');
        $_GET = ['page' => 2];
        $pagination = new Pagination(10);

        self::assertTrue($pagination->getStart() === 2, 'Pagination offset is incorrect.');
        self::assertTrue(str_contains($pagination->getPages(), 'admin/users?search=Alice&page=3'), 'Pagination lost path segments or query filters.');
        self::assertTrue(str_contains((string) $pagination, 'page-item active'), 'Pagination did not render.');

        $_GET = [];
        $app->request = new Request('/users');
        $pagination = new Pagination(0);
        self::assertTrue($pagination->getStart() === 0, 'Empty pagination offset is incorrect.');
        $this->assertThrows(fn() => new Pagination(10, 0), InvalidArgumentException::class);
    }

    public function testViewRendering(): void {
        $app = app();

        Config::$views = __DIR__ . '/fixtures/views';
        $view = new View('test');

        self::assertTrue(trim($view->renderPartial('message', ['message' => '<first>'])) === '<p>&lt;first&gt;</p>', 'Partial rendering failed.');
        self::assertTrue(str_contains($view->render('message', ['message' => 'one']), '<main>'), 'Layout was not rendered.');
        self::assertTrue(str_contains($view->render('message', ['message' => 'two']), '<main>'), 'Layout disappeared on repeated rendering.');
        self::assertTrue(!str_contains($view->render('message', ['message' => 'three'], false), '<main>'), 'Layout could not be disabled.');
    }
}
