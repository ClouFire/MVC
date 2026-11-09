<?php

namespace PHPFramework\Middleware;

class Auth {

    public function handle(): void {
        if (!isAuth()) {
            session()->setFlash('error', 'Forbidden access');
            response()->redirect('/login');
        }
    }

}
