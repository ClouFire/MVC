<?php

namespace App\Controllers;

class TestLabController extends BaseController
{
    public function clearCache(): void
    {
        cache()->clear();
        $user = auth();
        db()->logTestAction($user['id'] ?? null, 'clear_cache', [
            'scope' => 'application',
        ]);
        session()->setFlash('success', 'Application cache cleared.');
        // The test lab is an embedded panel, so there is no standalone page to redirect back to.
        response()->redirect('/');
    }

    public function executePhp(): void
    {
        if (!\PHPFramework\Config::$testLabPhpExecutionEnabled) {
            session()->setFlash('php_execution', [
                'status' => 'error',
                'output' => '',
                'return' => '',
                'error' => 'PHP execution is disabled by the environment configuration.',
            ]);
            response()->redirect('/');
        }

        $code = trim((string) request()->post('php_code'));
        if ($code === '') {
            session()->setFlash('php_execution', [
                'status' => 'error',
                'output' => '',
                'return' => '',
                'error' => 'Enter PHP code before executing it.',
            ]);
            response()->redirect('/');
        }

        $startedAt = microtime(true);
        $output = '';
        $returnValue = null;
        $error = null;

        ob_start();
        try {
            // eval() runs with the application autoloader, helpers, and current controller scope available.
            $returnValue = eval($code);
        } catch (\Throwable $exception) {
            $error = $exception::class . ': ' . $exception->getMessage();
        } finally {
            $output = (string) ob_get_clean();
        }

        $result = [
            'status' => $error === null ? 'success' : 'error',
            'output' => $output,
            'return' => var_export($returnValue, true),
            'error' => $error,
        ];
        $user = auth();
        db()->logTestAction($user['id'] ?? null, 'execute_php', [
            'status' => $result['status'],
            'code_sha256' => hash('sha256', $code),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);
        session()->setFlash('php_execution', $result);
        response()->redirect('/');
    }
}
