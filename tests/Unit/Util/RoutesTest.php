<?php

namespace Util;

use App\Models\Admin;
use App\Util\Routes;
use Tests\TestCase;

class RoutesTest extends TestCase
{

    public function testNav()
    {
        $admin = Admin::find(1);
        $routes = new Routes($admin);
        dd($routes->nav());
    }

    public function testRoutes()
    {
        $admin = Admin::find(1);
        $routes = new Routes($admin);
        dd($routes->routes());
    }
}
