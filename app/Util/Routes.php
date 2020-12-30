<?php

namespace App\Util;

use App\Models\Admin;
use Illuminate\Support\Collection;

class Routes
{
    private $admin;

    /**
     * Routes constructor.
     * @param Admin $admin
     */
    public function __construct(Admin $admin)
    {
        $this->setAdmin($admin);
    }

    public function routes(): Collection
    {
        if ($this->getAdmin()->status === 1) {
            $permissions = $this->permissionCollect();
            $permissions = $this->sortByDesc($permissions);
            $permissions = $this->formatRoutes($permissions);
            $permissions = $this->formatRoutesChildren($permissions);
        } else {
            $permissions = collect();
        }
        return $permissions->merge([[
            'path' => '*',
            'redirect' => '/404',
            'hidden' => true
        ]]);
    }

    private function permissionCollect(): Collection
    {
        $permissions = $this->getAdmin()->getAllPermissions()
            ->where('guard_name', '=', 'admin')
            ->toArray();
        $collect = [];
        $pivots = [];
        foreach ($permissions as $permission) {
            $pivots[$permission['id']][] = $permission['pivot'];
            $permission['pivots'] = $pivots[$permission['id']];
            $collect[$permission['id']] = $permission;
        }
        return collect($collect);
    }

    private function sortByDesc(Collection $permissions): Collection
    {
        $permissions = Arr::arraySort($permissions->toArray(), 'sort');
        return collect($permissions);
    }

    private function formatRoutes(Collection $permissions): Collection
    {
        return $permissions->map(function ($value) {
            $info = [];
            $info['id'] = $value['id'];
            $info['pid'] = $value['pid'];
            $info['path'] = $value['path'];
            $info['component'] = $value['component'];
            $info['name'] = $value['name']; // 设定路由的名字，一定要填写不然使用<keep-alive>时会出现各种问题
            $roles = [];
            if (isset($value['pivots'])) {
                foreach ($value['pivots'] as $pivot) {
                    if (isset($pivot['role_id'])) {
                        $roles[] = $pivot['role_id'];
                    } else {
                        $roles[] = $pivot['model_type'] . '\\' . $pivot['model_id'];
                    }
                }
            }
            $info['meta'] = [
                'title' => $value['name'], // 设置该路由在侧边栏和面包屑中展示的名字
                'icon' => $value['icon'], // 设置该路由的图标，支持 svg-class，也支持 el-icon-x element-ui 的 icon
                // 设置该路由进入的权限，支持多个权限叠加
                'roles' => $roles,
                'noCache' => true, // 如果设置为true，则不会被 <keep-alive> 缓存(默认 false)
                'breadcrumb' => true, //  如果设置为false，则不会在breadcrumb面包屑中显示(默认 true)
                'affix' => false, // 若果设置为true，它则会固定在tags-view中(默认 false)
            ];
            // 当设置 true 的时候该路由不会在侧边栏出现 如401，login等页面，或者如一些编辑页面/edit/1
            $info['hidden'] = $value['hidden'] ? true : false;
            // 当设置 noRedirect 的时候该路由在面包屑导航中不可被点击
            if ($value['component'] === 'layout/Layout' || $value['component'] === 'rview') {
                $info['redirect'] = 'noRedirect';
            }
            return $info;
        });
    }

    private function formatRoutesChildren(Collection $permissions): Collection
    {
        $permissions = Arr::getTree($permissions->toArray());

        foreach ($permissions as $key => $value) {
            if ($value['pid'] === 0 && $value['component'] !== 'layout/Layout' && $value['hidden'] === false) {
                $component = $value['component'];
                $permissions[$key]['component'] = 'layout/Layout';
                $permissions[$key]['redirect'] = 'noRedirect';
                $permissions[$key]['meta']['breadcrumb'] = false;
                $permissions[$key]['children'][] = [
                    'path' => 'index',
                    'component' => $component,
                    'name' => $value['name'],
                    'hidden' => $value['hidden'],
                    'meta' => [
                        'title' => $value['meta']['title'],
                        'icon' => $value['meta']['icon'],
                        'roles' => $value['meta']['roles'],
                        'noCache' => true,
                        'breadcrumb' => true,
                        'affix' => false,
                    ]
                ];
                unset($permissions[$key]['name']);
            }
        }

        return collect($permissions);
    }

    /**
     * @return Admin
     */
    public function getAdmin(): Admin
    {
        return $this->admin;
    }

    /**
     * @param Admin $admin
     * @return Routes
     */
    private function setAdmin(Admin $admin)
    {
        $this->admin = $admin;
        return $this;
    }
}
