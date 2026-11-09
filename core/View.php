<?php

namespace PHPFramework;

class View {

    public string $layout;
    public string $content = '';

    public function __construct(string $layout) {
        $this->layout = $layout;
    }

    public function render(string $view, array $data = [], $layout = ''): string {
        extract($data);
        $viewFile = Config::$views . "/{$view}.php";

        if (file_exists($viewFile)) {
            ob_start();
            require $viewFile;
            $this->content = ob_get_clean();
        } else {
            abort("View not found: {$viewFile}", 500);
        }

        if (false === $layout) {
            return $this->content;
        }

        $layoutName = $layout ?: $this->layout;
        $layoutFile = Config::$views . "/layouts/{$layoutName}.php";

        if (file_exists($layoutFile)) {
            ob_start();
            require_once $layoutFile;

            return ob_get_clean();
        } else {
            abort("View not layout: {$layoutFile}", 500);
        }
    }

    public function renderPartial($view, $data = []): string {
        extract($data);
        $viewFile = Config::$views . "/{$view}.php";

        if (file_exists($viewFile)) {
            ob_start();
            require $viewFile;

            return ob_get_clean();
        } else {
            return "File {$viewFile} not found";
        }
    }

}
