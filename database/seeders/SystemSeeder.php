<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=SystemSeeder
     *
     * @return void
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $top = Permission::findByName('system', 'admin');
        $list = Permission::create(['pid' => $top->id, 'name' => 'systemConfig', 'title' => '系统配置', 'icon' => 'fa fa-gears', 'sort' => 2, 'path' => '/systemConfig', 'component' => 'system/config', 'guard_name' => 'admin', 'hidden' => 0]);
        $role = Role::findByName('Admin', 'admin');
        $role->givePermissionTo([
            $list,
        ]);
    }
}
