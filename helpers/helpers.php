<?php

use Symfony\Component\VarDumper\Cloner\Data;

function app(): \PHPFramework\Application
{
    return \PHPFramework\Application::$app;
}

function request(): \PHPFramework\Request
{
    return app()->request;
}

function response(): \PHPFramework\Response
{
    return app()->response;
}

function view($view = '', $data = [], $layout = ''): string|\PHPFramework\View
{
    if($view)
    {
        if($page = cache()->get($view)) return $page; 
        return app()->view->render($view, $data, $layout);
    }
    return app()->view;
}

function abort($error = '', $code = 404)
{
    response()->setResponseCode($code);
    echo view("errors/{$code}", ['error'=> $error], false);
    die;
}

function basePath($path = ''): string
{
    return PATH . $path;
}

function db(): \PHPFramework\Database
{
    return app()->db;
}

function session(): \PHPFramework\Session
{
    return app()->session;
}

function cache(): \PHPFramework\Cache
{
    return app()->cache;
}
function get_alerts(): void
{
    if (!empty($_SESSION['flash']))
    {
        foreach($_SESSION['flash'] as $key => $msg)
        {
            echo view()->renderPartial("incs/alert_{$key}", ["flash_{$key}" => session()->getFlash($key)]);
        }
    }    
}

function get_errors($field): string
{
    $output = '';
    $errors = session()->get('form_errors');
    if(isset($errors[$field]))
    {
        $output .= '<div class="invalid-feedback d-block"><ul class="list-unstyled">';
        foreach($errors[$field] as $error)
        {
            $output .= "<li>$error</li>";
        }
        $output .= '</ul></div>';
    }

    return $output;
}

function old($field): string
{
    return isset(session()->get('form_data')[$field]) ? h(session()->get('form_data')[$field]) : '';
}

function h($str): string
{
    return htmlspecialchars($str, ENT_QUOTES);
}

function getValidationClass($field): string
{
    $errors = session()->get('form_errors');
    if(empty($errors)) return '';
    return isset($errors[$field]) ? 'is-invalid' : 'is-valid';
}

function getCsrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . session()->get('csrf_token') .'">';
}

function isAuth(): bool
{
    return false;
}


