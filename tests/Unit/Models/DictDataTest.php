<?php

namespace Models;

use App\Models\DictData;
use Tests\TestCase;

class DictDataTest extends TestCase
{

    public function testSelectAll()
    {
        dd(DictData::selectAll());
    }
}
