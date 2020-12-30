<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Admin;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $system = Permission::create(['pid' => 0, 'name' => 'system', 'title' => '系统', 'icon' => 'el-icon-s-tools', 'path' => '/system', 'component' => 'layout/Layout', 'guard_name' => 'admin', 'hidden' => 0]);

        $permissionAndRole = Permission::create(['pid' => $system->id, 'name' => 'permission-role', 'title' => '权限管理', 'icon' => 'lock', 'path' => '/permission-role', 'component' => 'rview', 'guard_name' => 'admin', 'hidden' => 0]);

        $permission = Permission::create(['pid' => $permissionAndRole->id, 'name' => 'permission.permissions', 'title' => '权限列表', 'icon' => 'el-icon-key', 'path' => 'permissions', 'component' => 'permission/permissions', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $permission->id, 'name' => 'permission.create', 'title' => '添加权限', 'icon' => 'icon', 'path' => 'permission/create', 'component' => 'permission/create', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $permission->id, 'name' => 'permission.update', 'title' => '编辑权限', 'icon' => 'icon', 'path' => 'permission/update', 'component' => 'permission/update', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $permission->id, 'name' => 'permission.delete', 'title' => '删除权限', 'icon' => 'icon', 'path' => 'permission/delete', 'component' => 'permission/delete', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $permission->id, 'name' => 'permission.permission', 'title' => '权限详情', 'icon' => 'icon', 'path' => 'permission', 'component' => 'permission/permission', 'guard_name' => 'admin', 'hidden' => 1]);


        $role = Permission::create(['pid' => $permissionAndRole->id, 'name' => 'role.roles', 'title' => '角色列表', 'icon' => 'el-icon-s-custom', 'path' => 'roles', 'component' => 'role/roles', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $role->id, 'name' => 'role.create', 'title' => '添加角色', 'icon' => 'icon', 'path' => 'role/create', 'component' => 'role/create', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $role->id, 'name' => 'role.update', 'title' => '编辑角色', 'icon' => 'icon', 'path' => 'role/update', 'component' => 'role/update', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $role->id, 'name' => 'role.delete', 'title' => '删除角色', 'icon' => 'icon', 'path' => 'role/delete', 'component' => 'role/delete', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $role->id, 'name' => 'role.role', 'title' => '角色详情', 'icon' => 'icon', 'path' => 'role/role', 'component' => 'role/role', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $role->id, 'name' => 'role.syncPermissions', 'title' => '分配权限/目录', 'icon' => 'icon', 'path' => 'role/syncPermissions', 'component' => 'role/syncPermissions', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $role->id, 'name' => 'role.syncRoles', 'title' => '分配用户', 'icon' => 'icon', 'path' => 'role/syncRoles', 'component' => 'role/syncRoles', 'guard_name' => 'admin', 'hidden' => 1]);

        $admin = Permission::create(['pid' => 0, 'name' => 'admin.admins', 'title' => '管理员列表', 'icon' => 'el-icon-user-solid', 'path' => '/admins', 'component' => 'admin/admins', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $admin->id, 'name' => 'admin.create', 'title' => '添加管理员', 'icon' => 'icon', 'path' => 'admin/create', 'component' => 'admin/create', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $admin->id, 'name' => 'admin.update', 'title' => '编辑管理员', 'icon' => 'icon', 'path' => 'admin/update', 'component' => 'admin/update', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $admin->id, 'name' => 'admin.delete', 'title' => '删除管理员', 'icon' => 'icon', 'path' => 'admin/delete', 'component' => 'admin/delete', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $admin->id, 'name' => 'admin.admin', 'title' => '管理员详情', 'icon' => 'icon', 'path' => 'admin/admin', 'component' => 'admin/admin', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $admin->id, 'name' => 'admin.syncPermissions', 'title' => '授权权限', 'icon' => 'icon', 'path' => 'admin/syncPermissions', 'component' => 'admin/syncPermissions', 'guard_name' => 'admin', 'hidden' => 1]);

        $activeLog = Permission::create(['pid' => $system->id, 'name' => 'activeLog.activeLogs', 'title' => '操作记录', 'icon' => 'el-icon-tickets', 'path' => '/activeLogs', 'component' => 'activeLog/activeLogs', 'guard_name' => 'admin', 'hidden' => 0]);

        $nginxLog = Permission::create(['pid' => $system->id, 'name' => 'nginx.logs', 'title' => 'NGINX记录', 'icon' => 'el-icon-tickets', 'path' => '/nginxLogs', 'component' => 'nginx/logs', 'guard_name' => 'admin', 'hidden' => 0]);

        $exceptionError = Permission::create(['pid' => $system->id, 'name' => 'exceptionError.exceptionErrors', 'title' => '异常记录', 'icon' => 'el-icon-warning', 'path' => '/exceptionErrors', 'component' => 'exceptionError/exceptionErrors', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $exceptionError->id, 'name' => 'exceptionError.amended', 'title' => '修复异常', 'icon' => 'el-icon-warning', 'path' => 'exceptionErrors/amended', 'component' => 'exceptionError/amended', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $system->id, 'name' => 'exceptionError.logFiles', 'title' => 'LOG日志', 'icon' => 'el-icon-tickets', 'path' => '/exceptionErrors/logFiles', 'component' => 'exceptionError/logFiles', 'guard_name' => 'admin', 'hidden' => 0]);

        $user = Permission::create(['pid' => 0, 'name' => 'user.users', 'title' => '用户列表', 'icon' => 'el-icon-user', 'path' => '/users', 'component' => 'user/users', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $user->id, 'name' => 'user.create', 'title' => '添加用户', 'icon' => 'icon', 'path' => 'user/create', 'component' => 'user/create', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $user->id, 'name' => 'user.update', 'title' => '编辑用户', 'icon' => 'icon', 'path' => 'user/update', 'component' => 'user/update', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $user->id, 'name' => 'user.delete', 'title' => '删除用户', 'icon' => 'icon', 'path' => 'user/delete', 'component' => 'user/delete', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $user->id, 'name' => 'user.user', 'title' => '用户详情', 'icon' => 'icon', 'path' => 'user/user', 'component' => 'user/user', 'guard_name' => 'admin', 'hidden' => 1]);

        $file = Permission::create(['pid' => $system->id, 'name' => 'file.files', 'title' => '文件管理', 'icon' => 'el-icon-folder', 'path' => '/files', 'component' => 'file/files', 'guard_name' => 'admin', 'hidden' => 0]);
        Permission::create(['pid' => $file->id, 'name' => 'file.makeDirectory', 'title' => '创建文件夹', 'icon' => 'el-icon-folder-add', 'path' => 'file/makeDirectory', 'component' => 'file/makeDirectory', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $file->id, 'name' => 'file.deleteDirectory', 'title' => '删除文件夹', 'icon' => 'el-icon-folder-delete', 'path' => 'file/deleteDirectory', 'component' => 'file/deleteDirectory', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $file->id, 'name' => 'file.upload', 'title' => '上传文件', 'icon' => 'el-icon-upload', 'path' => 'file/upload', 'component' => 'file/upload', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $file->id, 'name' => 'file.download', 'title' => '下载文件', 'icon' => 'el-icon-download', 'path' => 'file/download', 'component' => 'file/download', 'guard_name' => 'admin', 'hidden' => 1]);
        Permission::create(['pid' => $file->id, 'name' => 'file.delete', 'title' => '删除文件', 'icon' => 'el-icon-delete', 'path' => 'file/delete', 'component' => 'file/delete', 'guard_name' => 'admin', 'hidden' => 1]);


        $role1 = Role::create(['name' => 'Admin', 'guard_name' => 'admin']);
        $role1->givePermissionTo([
            'system',
            'permission-role',
            'permission.permissions',
            'permission.permission',
            'permission.create',
            'permission.update',
            'permission.delete',
            'role.roles',
            'role.role',
            'role.create',
            'role.update',
            'role.delete',
            'role.syncPermissions',
            'role.syncRoles',
            'admin.admins',
            'admin.admin',
            'admin.create',
            'admin.update',
            'admin.delete',
            'admin.syncPermissions',
            'activeLog.activeLogs',
            'nginx.logs',
            'exceptionError.exceptionErrors',
            'exceptionError.amended',
            'exceptionError.logFiles',
            'user.users',
            'user.user',
            'user.create',
            'user.update',
            'user.delete',
            'file.files',
            'file.makeDirectory',
            'file.deleteDirectory',
            'file.upload',
            'file.download',
            'file.delete',
        ]);

        $user = Admin::find(1);
        $user->assignRole('Admin');
        $role2 = Role::create(['name' => 'Test', 'guard_name' => 'admin']);
        $role2->givePermissionTo([
            'system',
            'permission-role',
            'permission.permissions',
            'permission.permission',
            'role.roles',
            'role.role',
            'admin.admins',
            'admin.admin',
            'activeLog.activeLogs',
            'user.users',
        ]);
        $user = Admin::find(2);
        $user->assignRole('Test');
    }
}
