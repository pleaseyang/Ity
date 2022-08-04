<?php

namespace Models;

use App\Models\DictType;
use App\Models\GenTable;
use App\Models\GenTableColumn;
use App\Util\Gen;
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
        $m = GenTableColumn::where('name', 'admin_id')->first();
        $m->setForeignShow(['name', 'type']);
        $m = GenTableColumn::where('name', 'number')->first();
        $m->setUnique();
        dd(GenTable::gen('test_db', 0, '小学生'));
    }

    public function testSetForeignShow()
    {
        $m = GenTableColumn::where('name', 'admin_id')->first();
        $m->setForeignShow(['name', 'type']);
    }

    public function testGenTableTest()
    {
        $data = Gen::getTableInfo('exception_errors');
        GenTable::importTable('exception_errors');
        GenTable::importTable('password_resets');
//        dd(GenTable::gen('gen_tables', 0, '代码生成'));
    }
}
