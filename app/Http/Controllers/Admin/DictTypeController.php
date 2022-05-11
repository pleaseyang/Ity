<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DictType\CreateRequest;
use App\Http\Requests\Admin\DictType\GetListRequest;
use App\Http\Requests\Admin\DictType\IdRequest;
use App\Http\Requests\Admin\DictType\UpdateRequest;
use App\Http\Response\ApiCode;
use App\Models\DictType;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class DictTypeController extends Controller
{
    /**
     * 列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function list(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(DictType::list($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }
    /**
     * 下拉
     *
     * @return Response
     */
    public function select(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'select' => DictType::selectAll()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 详情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function info(IdRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(DictType::find($validated['id']))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 创建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(DictType::create($validated))
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $model = DictType::find($validated['id']);
        $model->update($validated);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($model)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    /**
     * 删除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $validated = $request->validated();
        DictType::whereId($validated['id'])->delete();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.delete.success'))
            ->build();
    }
}
