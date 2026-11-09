<?php

namespace PHPFramework\Middleware;

use PHPFramework\ResponseMiddleware;

class CachePage implements ResponseMiddleware {
    public function handle(callable $next): mixed {
        if (!request()->isGet()
            || !empty($_SESSION['flash'])
            || $this->preventsCaching()
            || (http_response_code() !== false && http_response_code() !== 200)) {
            return $next();
        }

        $key = $this->getKey();
        $content = cache()->get($key);

        if (is_string($content)) {
            return $content;
        }

        $sessionBefore = $_SESSION ?? [];
        $headersBefore = headers_list();
        $bufferLevel = ob_get_level();

        ob_start();

        try {
            $content = $next();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($output !== '') {
            echo $output;

            return $content;
        }

        if (is_string($content)
            && (http_response_code() === false || http_response_code() === 200)
            && headers_list() === $headersBefore
            && ($_SESSION ?? []) === $sessionBefore
            && $this->getKey() === $key) {
            cache()->set($key, $content, 300);
        }

        return $content;
    }

    public function getKey(): string {
        return 'page:v2:' . hash('sha256', serialize([
            $_SERVER['HTTP_HOST'] ?? '',
            request()->getMethod(),
            $_SERVER['REQUEST_URI'] ?? request()->uri,
            $_SERVER['HTTP_ACCEPT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_COOKIE,
            request()->isAjax(),
            session_id(),
            $_SESSION ?? [],
        ]));
    }

    private function preventsCaching(): bool {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Set-Cookie:') === 0
                || (stripos($header, 'Cache-Control:') === 0
                    && preg_match('/\b(no-store|no-cache|private)\b/i', $header))) {
                return true;
            }
        }

        return false;
    }
}
