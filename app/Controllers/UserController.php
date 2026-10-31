<?php

namespace App\Controllers;
use App\Models\User;
use PHPFramework\Pagination;

class UserController extends BaseController
{

    public function register()
    {
        return view("user/register", ["title" => "Register page",]);
    }

    public function store()
    {
        $model = new User();
        $model->loadData();
        $model->addValidationRule('uniqueName', function($field, $value, $params, $fields){
            $data = explode(',', $params[0]);
            $user = db()->findOne($data[0], $value, $data[1]);
            return !$user;
        }, 'This name already exists');
        $model->addValidationRule('uniqueEmail', function($field, $value, $params, $fields){
            $data = explode(',', $params[0]);
            $user = db()->findOne($data[0], $value, $data[1]);
            return !$user;
        }, 'This email already exists');
        
        if (!$model->validate())
        {
            session()->setFlash("error", "Validation error");
            session()->set('form_errors', $model->getErrors());
            session()->set('form_data', $model->attrs);
        }
        else
        {
            $model->attrs['password'] = password_hash($model->attrs['password'], PASSWORD_DEFAULT);
            if($id = $model->save())
            {
                session()->remove('form_errors');
                session()->remove('form_data');
                session()->setFlash('success', 'Registration completed. You can now log in.');
                response()->redirect('/login');
            }
            else
            {
                session()->setFlash('error', 'Reg error');
            }
        }

        response()->redirect("/register");
    }

    public function login()
    {
        if (isAuth()) {
            response()->redirect('/dashboard');
        }

        return view("user/login", ["title" => "Login page",]);
    }

    public function authenticate()
    {
        $data = [
            'email' => trim((string) request()->post('email')),
            'password' => (string) request()->post('password'),
        ];
        $model = new User();

        if (!$model->validate($data, [
            'required' => ['email', 'password'],
            'email' => ['email'],
        ])) {
            session()->setFlash('error', 'Please check the highlighted fields.');
            session()->set('form_errors', $model->getErrors());
            session()->set('form_data', ['email' => $data['email']]);
            response()->redirect('/login');
        }

        $user = db()->findOne('users', $data['email'], 'email');
        if (!$user || !password_verify($data['password'], $user['password'])) {
            session()->setFlash('error', 'Invalid email or password.');
            session()->remove('form_errors');
            session()->set('form_data', ['email' => $data['email']]);
            response()->redirect('/login');
        }

        session()->regenerate();
        session()->set('user', [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
        session()->remove('form_errors');
        session()->remove('form_data');
        session()->setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        response()->redirect('/dashboard');
    }

    public function logout(): void
    {
        session()->remove('user');
        session()->regenerate();
        session()->setFlash('success', 'You have been logged out.');
        response()->redirect('/login');
    }

    public function index()
    {
        $users_cnt = db()->countAll('users');
        $limit = \PHPFramework\Config::$paginationSettings['perPage'];
        $pagination = new Pagination($users_cnt);
        $pagination->getPages();
        $users = db()->findRange('users', $limit, $pagination->getStart());

        return view('user/index', [
            'title' => 'Index page',
            'users' => $users,
            'pagination' => $pagination,
        ]);
    }
}
