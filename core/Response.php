<?php

namespace PHPFramework;

class Response {

    public function setResponseCode(int $code): void {
        http_response_code($code);
    }

    public function redirect($url = ''): void {
        if ($url) {
            $redirect = rtrim(\PHPFramework\Config::$path, '/') . '/' . ltrim($url, '/');

        } else {
            $redirect = $_SERVER['HTTP_REFERER'] ?? \PHPFramework\Config::$path;
        }

        header("Location: $redirect");
        die();
    }
}
