<?php

namespace Models;

use App\Models\Admin;
use Tests\TestCase;

class AdminTest extends TestCase
{

    public function testGetList()
    {
        $res = Admin::getList([
            "offset" => 0,
            "limit" => 10,
            "order" => "descending",
            "name" => 'test'
        ]);
        dd($res);
    }
}
