<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\AllRolesRequest;
use App\Http\Requests\Admin\Role\CreateRequest;
use App\Http\Requests\Admin\Role\GetListRequest;
use App\Http\Requests\Admin\Role\SyncPermissionsRequest;
use App\Http\Requests\Admin\Role\SyncRolesRequest;
use App\Http\Requests\Admin\Role\UpdateRequest;
use App\Http\Response\ApiCode;
use App\Models\Permission;
use App\Models\Role;
use App\Notifications\RoleChange;
use Exception;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * 获取角色详情
     *
     * @param Request $request
     * @return Response
     */
    public function role(Request $request): Response
    {
        $id = $request->post('id', 0);
        try {
            $role = Role::whereId($id)->first();
            $permissions = $role->permissions;
            $role->users;
            $permissionIdList = $permissions->mapWithKeys(function ($permission, $key) {
                return [$key => $permission->id];
            });
            $role->permissionIdList = $permissionIdList;
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($role)
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (RoleDoesNotExist $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage(__('message.common.search.fail'))
                ->build();
        }
    }

    /**
     * 获取角色列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function roles(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Role::getList($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 获取全部角色列表
     *
     * @param AllRolesRequest $request
     * @return Response
     */
    public function allRoles(AllRolesRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'roles' => Role::getAllRoles($validated)
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }


    /**
     * 创建角色
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Role::create($validated))
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 更新角色
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $id = $validated['id'];
        unset($validated['id']);
        $role = Role::whereId($id)->first();
        $result = $role->update($validated);
        if ($result) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($role)
                ->withMessage(__('message.common.update.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withData($role)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }

    /**
     * 删除角色
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id', 0);
        try {
            $role = Role::whereId($id)->first();
            $role->delete();
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.delete.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage(__('message.common.delete.fail'))
                ->build();
        }
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
        $role = Role::whereId($validated['id'])->first();
        $permissions = isset($validated['permissions']) ?
            Permission::whereIn('id', $validated['permissions'])->get() :
            [];
        $role->syncPermissions($permissions);
        activity()
            ->useLog('role')
            ->performedOn($role)
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log('update permissions');
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($role)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    /**
     * 分配用户
     *
     * @param SyncRolesRequest $request
     * @return Response
     */
    public function syncRoles(SyncRolesRequest $request): Response
    {
        $validated = $request->validated();
        $guard = $request->guard();
        $roles = isset($validated['roles']) ?
            Role::whereIn('id', $validated['roles'])->get() :
            [];
        $guard->syncRoles($roles);
        activity()
            ->useLog('role')
            ->performedOn(new Role())
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log('update roles');
        $guard->notify(new RoleChange($roles));
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($guard)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }
}
