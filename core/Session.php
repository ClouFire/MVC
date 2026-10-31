<?php

namespace PHPFramework;

class Session
{

    public function __construct()
    {
        session_start();
    }

    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        if(isset($_SESSION[$key]))
        {
            unset($_SESSION[$key]);
        }
    }

    public function setFlash($key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    public function getFlash($key, $default = null)
    {
        if (isset($_SESSION['flash'][$key]))
        {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
        }
        return $value ?? $default;
    }

}
