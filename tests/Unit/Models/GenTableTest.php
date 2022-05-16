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
        dd(GenTable::importTable('test_db'));
    }

    public function testGen()
    {
        dd(GenTable::gen('test_db', 0, '小学生'));
    }

    public function testSetDict()
    {
        $m = GenTableColumn::where('id', 30)->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        dd($m);
    }

    public function testSetType()
    {
        $m = GenTableColumn::where('id', 29)->first();
        $m->setType('file');
        $m = GenTableColumn::where('id', 31)->first();
        $m->setType('editor');
    }
}
