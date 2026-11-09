<?php

namespace PHPFramework;

class Request {

    public string $uri;

    public function __construct($uri) {
        $this->uri = trim(urldecode($uri), '/');
    }

    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getPath(): string {
        return $this->removeQueryString();
    }

    protected function removeQueryString(): string {
        if (!$this->uri) {
            return '';
        }

        $path = explode('?', $this->uri, 2)[0];
        $path = trim($path, '/');
        $basePath = trim(\PHPFramework\Config::$path, '/');

        if ($basePath !== '') {
            $path = preg_replace(
                '#^' . preg_quote($basePath, '#') . '/?#',
                '',
                $path
            );
        }

        return trim($path, '/');
    }

    public function isGet(): bool {
        return $this->getMethod() == 'GET';
    }

    public function isPost(): bool {
        return $this->getMethod() == 'POST';
    }

    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function getData(): array {
        $data = [];
        $requestData = $this->isPost() ? $_POST : $_GET;

        foreach ($requestData as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    public function post($data)  {
        if (isset($_POST[$data])) {
            return $_POST[$data];
        }

        return false;
    }

    public function get($data, $default = 0)  {
        if (isset($_GET[$data])) {
            return $_GET[$data];
        }

        return $default;
    }
}
