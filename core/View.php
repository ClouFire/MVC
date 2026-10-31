<?php

namespace PHPFramework;

class View
{

    public string $layout;
    public string $content = '';

    public function __construct(string $layout)
    {
        $this->layout = $layout;
    }

    public function render(string $view, array $data = [], $layout = ''): string
    {
        extract($data);
        $view_file = Config::$views . "/{$view}.php";
        if (file_exists($view_file)) 
        {
            ob_start(); 
            require $view_file;
            $this->content = ob_get_clean();
        }
        else
        {
            abort("View not found: {$view_file}", 500);
        }

        if(false === $layout)
        {
            return $this->content;
        }

        $layout_file = $layout ?: $this->layout;
        $layout_file = Config::$views . "/layouts/{$layout_file}.php";
        if(file_exists($layout_file))
        {
            ob_start();
            require_once $layout_file;
            return ob_get_clean();
        }
        else
        {
            abort("View not layout: {$layout_file}", 500);
        }
    }

    public function renderPartial($view, $data = []): string
    {
        extract($data);
        $view_file = Config::$views . "/{$view}.php";
        if (file_exists($view_file)) 
        {
            ob_start(); 
            require $view_file;
            return ob_get_clean();
        }
        else
        {
            return "File {$view_file} not found";
        }
    }

}
