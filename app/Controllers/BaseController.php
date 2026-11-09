<?php

namespace App\Controllers;

use PHPFramework\Controller;

class BaseController extends Controller {

    public function __construct() {
        $isAuthenticated = isAuth();
        $currentPath = request()->getPath();

        $menuState = $isAuthenticated ? 'auth' : 'guest';
        $menuKey = "menu:{$menuState}:{$currentPath}:v5";
        $menu = cache()->get($menuKey);

        if (!$menu) {
            $menu = $this->renderMenu();

            cache()->set($menuKey, $menu);
        }

        app()->set('menu', $menu);
    }

    public function renderMenu(): string {
        return view()->renderPartial('incs/menu');
    }
}
