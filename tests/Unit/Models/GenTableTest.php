<?php

namespace Models;

use App\Models\DictType;
use App\Models\GenTable;
use App\Models\GenTableColumn;
use Tests\TestCase;

class GenTableTest extends TestCase
{

    public function testGetImportTableList()
    {
        dd(GenTable::getImportTableList());
    }

    public function testImportTableList()
    {
        dd(GenTable::importTable('users_copy'));
    }

    public function testGen()
    {
        dd(GenTable::gen('users_copy', 0, '用户'));
    }

    public function testSetDict()
    {
        $m = GenTableColumn::where('id', 2)->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        dd($m);
    }
}
