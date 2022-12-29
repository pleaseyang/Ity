<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\CreateRequest;
use App\Http\Requests\Admin\Admin\GetListRequest;
use App\Http\Requests\Admin\Admin\SyncPermissionsRequest;
use App\Http\Requests\Admin\Admin\UpdateRequest;
use App\Http\Requests\Admin\Admin\UpdateSelfRequest;
use App\Http\Response\ApiCode;
use App\Models\Admin;
use App\Models\Permission;
use App\Notifications\PermissionChange;
use App\Util\Routes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    /**
     * 管理员列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function admins(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Admin::getList($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 管理员详情
     *
     * @param Request $request
     * @return Response
     */
    public function admin(Request $request): Response
    {
        $id = $request->post('id', 0);
        $admin = Admin::find($id);
        if ($admin) {
            $admin->roles;
            $roleIds = $admin->roles->mapWithKeys(function ($role, $key) {
                return [$key => $role->id];
            });
            $admin->permissions;
            $permissionIds = $admin->permissions->mapWithKeys(function ($permission, $key) {
                return [$key => $permission->id];
            });
            $admin['roleIds'] = $roleIds;
            $admin['permissionIds'] = $permissionIds;
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($admin)
                ->withMessage(__('message.common.search.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.search.fail'))
            ->build();
    }

    /**
     * 创建管理员
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Admin::create($validated))
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 更新管理员
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $resultData = Admin::updateSave($validated);
        if ($resultData->isStatus()) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($resultData->getData('admin'))
                ->withMessage(__('message.common.update.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }


    /**
     * 删除管理员
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id', 0);
        $admin = Admin::find($id);
        if ($admin) {
            $admin->delete();
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.delete.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.delete.fail'))
            ->build();
    }


    /**
     * 自身更新
     *
     * @param UpdateSelfRequest $request
     * @return Response
     */
    public function updateSelf(UpdateSelfRequest $request): Response
    {
        $validated = $request->validated();
        /* @var Admin $admin */
        $admin = $request->user('admin');
        $validated['id'] = $admin->id;
        // 可删除
        if ($validated['id'] === 8) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage('测试账号不能修改信息')
                ->build();
        }
        $resultData = Admin::updateSave($validated);
        if ($resultData->isStatus()) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($resultData->getData('admin'))
                ->withMessage(__('message.common.update.success'))
                ->build();
        }
        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }

    /**
     * 授权权限
     *
     * @param SyncPermissionsRequest $request
     * @return Response
     */
    public function syncPermissions(SyncPermissionsRequest $request): Response
    {
        $validated = $request->validated();
        $admin = Admin::find($validated['id']);
        $permissions = isset($validated['permissions']) ?
            Permission::whereIn('id', $validated['permissions'])->get() :
            [];
        $admin->syncPermissions($permissions);
        activity()
            ->useLog('admin')
            ->performedOn($admin)
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log('update permissions');
        $admin->notify(new PermissionChange($permissions));
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($admin)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     */
    public function nav(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $routes = new Routes($admin);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'list' => $routes->nav()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    public function navSetNoCache(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $data = (array)$request->post('data');
        $routes = new Routes($admin);
        $data = collect($data)->mapWithKeys(function (array $array): array {
            return [$array['name'] => $array['no_cache']];
        });
        Cache::forget($routes->cacheKey());
        Cache::store('redis')->forever($routes->cacheKey(), $data);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    public function navSetAffix(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $data = (array)$request->post('data');
        $routes = new Routes($admin);
        $data = collect($data)->mapWithKeys(function (array $array): array {
            return [$array['name'] => $array['affix']];
        });
        Cache::forget($routes->affixKey());
        Cache::store('redis')->forever($routes->affixKey(), $data);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    public function select(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'select' => Admin::selectAll()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    public function setting(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $key = $request->post('key');
        $value = $request->post('value');
        if (!in_array($key, ['theme', 'tags_view', 'fixed_header', 'sidebar_logo', 'support_pinyin_search'])) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage(__('message.common.update.fail'))
                ->build();
        }
        $admin->$key = $value;
        $admin->save();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }
}
