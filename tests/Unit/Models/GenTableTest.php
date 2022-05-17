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
        $m = GenTableColumn::where('name', 'sex')->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        dd($m);
    }

    public function testSetType()
    {
        $m = GenTableColumn::where('name', 'avatar')->first();
        $m->setType('file');
        $m = GenTableColumn::where('name', 'content')->first();
        $m->setType('editor');
    }

    public function testGenTest()
    {
        GenTable::importTable('test_db');
        $m = GenTableColumn::where('name', 'sex')->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        $m = GenTableColumn::where('name', 'avatar')->first();
        $m->setType('image');
        $m = GenTableColumn::where('name', 'dangan')->first();
        $m->setType('file');
        $m = GenTableColumn::where('name', 'content')->first();
        $m->setType('editor');
        dd(GenTable::gen('test_db', 0, '小学生'));
    }
}
