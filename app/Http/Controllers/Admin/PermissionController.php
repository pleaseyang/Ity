<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Permission\CreateRequest;
use App\Http\Requests\Admin\Permission\DropRequest;
use App\Http\Requests\Admin\Permission\GetListRequest;
use App\Http\Requests\Admin\Permission\PermissionTreeRequest;
use App\Http\Requests\Admin\Permission\UpdateRequest;
use App\Http\Response\ApiCode;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    /**
     * 获取权限详情
     *
     * @param Request $request
     * @return Response
     */
    public function permission(Request $request): Response
    {
        $id = $request->post('id', 0);
        try {
            $permission = Permission::find($id);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($permission)
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (PermissionDoesNotExist $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage(__('message.common.search.fail'))
                ->build();
        }
    }

    /**
     * 获取权限列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function permissions(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Permission::getList($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 获取权限列表 Tree 结构
     *
     * @param PermissionTreeRequest $request
     * @return Response
     */
    public function permissionsTree(PermissionTreeRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'tree' => Permission::tree($validated)
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 更改排序与层级
     *
     * @param DropRequest $request
     * @return Response
     */
    public function drop(DropRequest $request): Response
    {
        $validated = $request->validated();
        $result = Permission::drop($validated['dragging'], $validated['drop'], $validated['type']);
        if ($result) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.update.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }

    /**
     * 创建权限
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Permission::create($validated))
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 更新权限
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $id = $validated['id'];
        unset($validated['id']);
        $permission = Permission::find($id);
        $result = $permission->update($validated);
        if ($result) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($permission)
                ->withMessage(__('message.common.update.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withData($permission)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }

    /**
     * 删除权限
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id', 0);
        $response = Permission::__deleted($id);
        if ($response['result']) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage($response['message'])
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage($response['message'])
            ->build();
    }
}
