<?php

namespace App\Controllers;

use App\Models\User;

class HomeController extends BaseController {

    public function getHomePage() {
        return view('home', ['title' => 'Home page']);
    }

    public function dashboard() {
        return view('dashboard', [
            'title' => 'Dashboard page',
            'testActionLog' => db()->getTestActionLog(),
        ]);
    }

    public function createUser(): void {
        $name = trim((string) request()->post('name'));
        $email = strtolower(trim((string) request()->post('email')));
        $password = trim((string) request()->post('password'));

        $name = $name !== '' ? $name : 'Lab User ' . random_int(1000, 9999);
        $email = $email !== '' ? $email : 'lab.' . bin2hex(random_bytes(5)) . '@example.test';
        $password = $password !== '' ? $password : bin2hex(random_bytes(6));

        $model = new User();

        $model->attrs = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'confirmPassword' => $password,
        ];

        $model->addValidationRule('uniqueName', function ($field, $value, $params, $fields) {
            $data = explode(',', $params[0]);

            return !db()->findOne($data[0], $value, $data[1]);
        }, 'This name already exists');

        $model->addValidationRule('uniqueEmail', function ($field, $value, $params, $fields) {
            $data = explode(',', $params[0]);

            return !db()->findOne($data[0], $value, $data[1]);
        }, 'This email already exists');

        if (!$model->validate()) {
            $user = auth();

            db()->logTestAction($user['id'] ?? null, 'create_test_user_failed', [
                'name' => $name,
                'email' => $email,
                'errors' => $model->getErrors(),
            ]);

            session()->setFlash('error', 'Please check the generated user fields.');
            session()->set('test_user_errors', $model->getErrors());

            session()->set('test_user_data', [
                'name' => $name,
                'email' => $email,
            ]);

            response()->redirect('/dashboard');
        }

        $model->attrs['password'] = password_hash($password, PASSWORD_DEFAULT);
        $createdUserId = $model->save();

        if (!$createdUserId) {
            $user = auth();

            db()->logTestAction($user['id'] ?? null, 'create_test_user_failed', [
                'name' => $name,
                'email' => $email,
                'errors' => ['database' => ['User could not be created.']],
            ]);

            session()->setFlash('error', 'User could not be created.');

            response()->redirect('/dashboard');
        }

        $user = auth();

        db()->logTestAction($user['id'] ?? null, 'create_test_user', [
            'created_user_id' => $createdUserId,
            'email' => $email,
        ]);

        session()->remove('test_user_errors');
        session()->remove('test_user_data');
        session()->setFlash('success', 'User created successfully.');

        session()->setFlash('created_credentials', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        response()->redirect('/dashboard');
    }
}
