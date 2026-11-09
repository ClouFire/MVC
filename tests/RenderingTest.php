<?php

use PHPFramework\Application;
use PHPFramework\Config;
use PHPFramework\Pagination;
use PHPFramework\Request;
use PHPFramework\Session;
use PHPFramework\View;

final class RenderingTest extends FrameworkTestCase {
    protected function setUp(): void {
        parent::setUp();
        configureTestApplication();

        $app = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
        Application::$app = $app;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        session_save_path(sys_get_temp_dir());

        $app->session = new Session();
        $app->request = new Request('/');
        $app->view = new View('default');
        $app->writeCsrfToken();
    }

    public function testLabDisplaysEscapedCredentialsValidationAndExecutionResults(): void {
        session()->set('user', ['id' => 1]);
        Config::$testLabPhpExecutionEnabled = true;
        Config::$testLabSqlConsoleEnabled = true;

        session()->setFlash('created_credentials', ['email' => '<email>', 'password' => '<secret>']);
        session()->set('test_user_errors', ['name' => ['<name>'], 'email' => ['<email>'], 'password' => ['<password>']]);
        session()->set('test_user_data', ['name' => '<name>', 'email' => '<email>']);
        session()->setFlash('php_execution', ['status' => 'error', 'output' => '<output>', 'return' => 'null', 'error' => '<error>']);
        session()->setFlash('sql_console', ['status' => 'error', 'error' => '<sql-error>']);

        $html = view()->renderPartial('incs/test_lab');

        foreach (['&lt;secret&gt;', '&lt;name&gt;', '&lt;password&gt;', '&lt;output&gt;', '&lt;sql-error&gt;'] as $value) {
            self::assertStringContainsString($value, $html);
        }

        self::assertNull(session()->get('test_user_errors'));
        self::assertNull(session()->getFlash('created_credentials'));

        foreach ([
            ['status' => 'success', 'columns' => ['name'], 'rows' => [['name' => '<Alice>']], 'affected_rows' => null],
            ['status' => 'success', 'columns' => ['name'], 'rows' => [], 'affected_rows' => null],
            ['status' => 'success', 'columns' => [], 'rows' => [], 'affected_rows' => 2],
        ] as $index => $result) {
            session()->setFlash('sql_console', $result);
            session()->setFlash('php_execution', ['status' => 'success', 'output' => '', 'return' => '42', 'error' => null]);

            $html = view()->renderPartial('incs/test_lab');

            self::assertStringContainsString(['&lt;Alice&gt;', 'The query returned no rows.', 'Affected rows: 2'][$index], $html);
        }
    }

    public function testDashboardEscapesAuditLogFields(): void {
        $html = view('dashboard', ['testActionLog' => [[
            'created_at' => '2026-11-09',
            'user_name' => '<user>',
            'action' => '<action>',
            'details' => '<details>',
        ]]], false);

        self::assertStringContainsString('&lt;user&gt;', $html);
        self::assertStringContainsString('&lt;action&gt;', $html);
        self::assertStringContainsString('&lt;details&gt;', $html);
    }

    public function testPaginationRendersBoundaryLinksWithAndWithoutFilters(): void {
        app()->request = new Request('/users');
        $_GET = ['page' => 5];

        $html = (new Pagination(20))->getPages();

        self::assertStringContainsString('aria-label="Start page"', $html);
        self::assertStringContainsString('aria-label="Last page"', $html);
        self::assertStringContainsString('/users?page=6', $html);
    }

    public function testMissingPartialReturnsADiagnostic(): void {
        self::assertStringContainsString('not found', view()->renderPartial('missing-partial'));
    }

    public function testModelMissingInputAndAlerts(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $user = new \App\Models\User();

        $user->loadData();

        self::assertSame('', $user->attrs['name']);
        session()->setFlash('error', '<failure>');

        ob_start();
        get_alerts();
        $html = ob_get_clean();

        self::assertStringContainsString('&lt;failure&gt;', $html);
        self::assertNull(session()->getFlash('error'));
    }
}
