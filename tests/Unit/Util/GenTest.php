<?php

namespace Util;

use App\Util\Gen;
use Tests\TestCase;

class GenTest extends TestCase
{

    public function testDbTables()
    {
        dd(Gen::getTableList());
    }

    public function testGetTableInfo()
    {
        dd(Gen::getTableInfo('users_copy1'));
    }

    public function testGetTableConfig()
    {
        dd(Gen::getTableConfig('users_copy1'));
    }

    public function testColumnMethodAndType()
    {
        dd(Gen::columnMethodAndType());
    }
}
