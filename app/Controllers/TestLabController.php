<?php

namespace App\Controllers;

class TestLabController extends BaseController {
    public function clearCache(): void {
        cache()->clear();
        $user = auth();

        db()->logTestAction($user['id'] ?? null, 'clear_cache', [
            'scope' => 'application',
        ]);

        session()->setFlash('success', 'Application cache cleared.');

        response()->redirect('/');
    }

    public function executePhp(): void {
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

    public function executeSql(): void {
        if (!\PHPFramework\Config::$testLabSqlConsoleEnabled) {
            $this->respondWithSqlResult([
                'status' => 'error',
                'columns' => [],
                'rows' => [],
                'affected_rows' => null,
                'error' => 'The SQL console is disabled by the environment configuration.',
            ]);
        }

        $sql = trim((string) request()->post('sql'));

        if ($sql === '') {
            $this->respondWithSqlResult([
                'status' => 'error',
                'columns' => [],
                'rows' => [],
                'affected_rows' => null,
                'error' => 'Enter an SQL statement before executing it.',
            ]);
        }

        $startedAt = microtime(true);

        $result = [
            'status' => 'success',
            'columns' => [],
            'rows' => [],
            'affected_rows' => null,
            'error' => null,
        ];

        try {
            db()->execute($sql);
            $statement = db()->getStatement();

            if ($statement instanceof \PDOStatement && $statement->columnCount() > 0) {
                $result['rows'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $result['columns'] = array_keys($result['rows'][0] ?? []);
            } elseif ($statement instanceof \PDOStatement) {
                $result['affected_rows'] = $statement->rowCount();
            }
        } catch (\Throwable $exception) {
            $result['status'] = 'error';
            $result['error'] = $exception::class . ': ' . $exception->getMessage();
        }

        $user = auth();

        db()->logTestAction($user['id'] ?? null, 'execute_sql', [
            'status' => $result['status'],
            'sql_sha256' => hash('sha256', $sql),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'rows' => count($result['rows']),
            'affected_rows' => $result['affected_rows'],
        ]);

        $this->respondWithSqlResult($result);
    }

    private function respondWithSqlResult(array $result): void {
        if (!request()->isAjax()) {
            session()->setFlash('sql_console', $result);
            response()->redirect('/');
        }

        response()->setResponseCode($result['status'] === 'success' ? 200 : 422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
