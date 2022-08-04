<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GenTablePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=GenTablePermissionSeeder
     *
     * @return void
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $top = Permission::findByName('system', 'admin');
        $list = Permission::create(['pid' => $top->id, 'name' => 'genTable.genTables', 'title' => '代码生成', 'icon' => 'fa fa-code', 'path' => '/genTables', 'component' => 'genTable/index', 'guard_name' => 'admin', 'hidden' => 0]);
        $role = Role::findByName('Admin', 'admin');
        $role->givePermissionTo([
            $list,
        ]);
    }
}
