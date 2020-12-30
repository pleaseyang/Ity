<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\CreateRequest;
use App\Http\Requests\Admin\User\GetListRequest;
use App\Http\Requests\Admin\User\UpdateRequest;
use App\Http\Response\ApiCode;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * 管理员列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function users(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(User::getList($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 用户详情
     *
     * @param Request $request
     * @return Response
     */
    public function user(Request $request): Response
    {
        $id = $request->post('id', 0);
        $user = User::find($id);
        if ($user) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($user)
                ->withMessage(__('message.common.search.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.search.fail'))
            ->build();
    }

    /**
     * 创建用户
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(User::create($validated))
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 更新用户
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $resultData = User::updateSave($validated);
        if ($resultData['result']) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($resultData['user'])
                ->withMessage(__('message.common.update.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.update.fail'))
            ->build();
    }

    /**
     * 删除用户
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        $id = $request->post('id', 0);
        $user = User::find($id);
        if ($user) {
            $user->delete();
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
}
