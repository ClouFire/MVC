<?php

namespace App\Controllers;

use PHPFramework\Controller;

class BaseController extends Controller
{
    
    public function __construct()
    {
        // app()->set('test', 'value');
        // app()->set('menu', $this->renderMenu());
        $menuState = isAuth() ? 'auth' : 'guest';
        $menuKey = "menu:{$menuState}:" . request()->getPath() . ':v5';
        if (!$menu = cache()->get($menuKey)) {
            $menu = $this->renderMenu();
            cache()->set($menuKey, $menu);
        }
        app()->set('menu', $menu);
    }

    public function renderMenu(): string //Можно сделать через запрос к БД и оттуда уже сформировать готовую верстку, но "так как проект локальный, воспользуюсь локальным хранилищем" (проверить эту теорию в гпт)
    {
        return view()->renderPartial('incs/menu');
    }
}
